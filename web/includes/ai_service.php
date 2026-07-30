<?php
/**
 * AI Plant Identification Service
 * Uses Pl@ntNet API with fallback to mock data
 */

require_once __DIR__ . '/../config/database.php';

class PlantIdentifier {
    
    /**
     * Identify plant from image file path
     */
    public static function identify(string $imagePath, string $organHint = 'auto'): array {
        // Try Pl@ntNet API first
        if (!empty(PLANTNET_API_KEY)) {
            $result = self::callPlantNet($imagePath, $organHint);
            if ($result['success']) {
                return $result;
            }
        }
        
        // Fallback to smart mock identification
        return self::mockIdentify($imagePath);
    }
    
    /**
     * Call Pl@ntNet API
     */
    private static function callPlantNet(string $imagePath, string $organ): array {
        $url = PLANTNET_API_URL . '?api-key=' . PLANTNET_API_KEY . '&include-related-images=true';
        
        $cfile = new CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath));
        
        $postData = [
            'images' => $cfile,
            'organs' => $organ === 'auto' ? 'auto' : $organ
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return ['success' => false, 'error' => 'PlantNet API error'];
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['results'])) {
            return ['success' => false, 'error' => 'Invalid API response'];
        }
        
        $candidates = [];
        $db = getDB();
        
        foreach (array_slice($data['results'], 0, 3) as $result) {
            $species = $result['species'] ?? [];
            $scientificName = $species['scientificNameWithoutAuthor'] ?? 'Unknown';
            $commonNames = $species['commonNames'] ?? [];
            $family = $species['family']['scientificNameWithoutAuthor'] ?? '';
            $score = round(($result['score'] ?? 0) * 100, 2);
            $refImage = $result['images'][0]['url']['m'] ?? '';
            
            // Check if species exists in our DB
            $stmt = $db->prepare("SELECT id FROM species WHERE scientific_name = ?");
            $stmt->execute([$scientificName]);
            $existing = $stmt->fetch();
            
            $candidates[] = [
                'species_id' => $existing ? $existing['id'] : null,
                'scientific_name' => $scientificName,
                'common_name' => $commonNames[0] ?? '',
                'family' => $family,
                'confidence' => $score,
                'reference_image' => $refImage
            ];
        }
        
        return [
            'success' => true,
            'source' => 'plantnet',
            'candidates' => $candidates
        ];
    }
    
    /**
     * Mock identification for demo/testing (uses existing species from DB)
     */
    private static function mockIdentify(string $imagePath): array {
        $db = getDB();
        
        // Get random species from database for demo
        $stmt = $db->query("SELECT id, scientific_name, common_name, family, reference_image_url FROM species ORDER BY RAND() LIMIT 3");
        $speciesList = $stmt->fetchAll();
        
        if (empty($speciesList)) {
            // Hardcoded fallback if DB is empty
            return [
                'success' => true,
                'source' => 'mock',
                'candidates' => [
                    [
                        'species_id' => null,
                        'scientific_name' => 'Ficus benghalensis',
                        'common_name' => 'Banyan Tree',
                        'family' => 'Moraceae',
                        'confidence' => 87.50,
                        'reference_image' => ''
                    ],
                    [
                        'species_id' => null,
                        'scientific_name' => 'Ficus religiosa',
                        'common_name' => 'Peepal Tree',
                        'family' => 'Moraceae',
                        'confidence' => 45.20,
                        'reference_image' => ''
                    ],
                    [
                        'species_id' => null,
                        'scientific_name' => 'Azadirachta indica',
                        'common_name' => 'Neem',
                        'family' => 'Meliaceae',
                        'confidence' => 12.30,
                        'reference_image' => ''
                    ]
                ]
            ];
        }
        
        $confidences = [87.50, 45.20, 12.30];
        $candidates = [];
        
        foreach ($speciesList as $i => $sp) {
            $candidates[] = [
                'species_id' => $sp['id'],
                'scientific_name' => $sp['scientific_name'],
                'common_name' => $sp['common_name'],
                'family' => $sp['family'],
                'confidence' => $confidences[$i] ?? round(mt_rand(500, 3000) / 100, 2),
                'reference_image' => $sp['reference_image_url'] ?? ''
            ];
        }
        
        return [
            'success' => true,
            'source' => 'mock',
            'candidates' => $candidates
        ];
    }
    
    /**
     * Find or create a species record
     */
    public static function findOrCreateSpecies(string $scientificName, string $commonName = '', string $family = '', string $source = 'plantnet'): string {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT id FROM species WHERE scientific_name = ?");
        $stmt->execute([$scientificName]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return $existing['id'];
        }
        
        $id = generateUUID();
        $stmt = $db->prepare("INSERT INTO species (id, scientific_name, common_name, family, ai_source) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $scientificName, $commonName, $family, $source]);
        
        return $id;
    }
    
    /**
     * Auto-detect zone based on lat/lng
     */
    public static function detectZone(float $lat, float $lng, string $institutionId): ?string {
        $db = getDB();
        
        // Find nearest zone center within 200m
        $stmt = $db->prepare("
            SELECT id, name, 
                   haversine_distance(center_lat, center_lng, ?, ?) AS distance
            FROM zones 
            WHERE institution_id = ? 
            HAVING distance < 200
            ORDER BY distance ASC 
            LIMIT 1
        ");
        $stmt->execute([$lat, $lng, $institutionId]);
        $zone = $stmt->fetch();
        
        return $zone ? $zone['id'] : null;
    }
}
