<?php
/**
 * ADD SAMPLE CLIENTS
 * For testing Clients CRUD module
 */

require './constant/connect.php';

echo "Adding sample clients to test module...\n\n";

$sampleClients = [
    [
        'code' => 'CL001',
        'name' => 'Sunrise Pharmacy',
        'phone' => '9876543210',
        'email' => 'sunrise@pharmacy.com',
        'billing' => 'Shop No. 5, Rajendra Nagar, Nashik',
        'shipping' => 'Shop No. 5, Rajendra Nagar, Nashik',
        'city' => 'Nashik',
        'state' => 'Maharashtra',
        'postal' => '422001',
        'gstin' => '27AABCT1234K1Z0',
        'business' => 'Retail',
        'terms' => 30,
        'limit' => 50000
    ],
    [
        'code' => 'CL002',
        'name' => 'Apollo Healthcare Distribution',
        'phone' => '9123456789',
        'email' => 'apollo@dist.com',
        'billing' => 'Plot 42, Industrial Area, Mumbai',
        'shipping' => 'Plot 42, Industrial Area, Mumbai',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal' => '400012',
        'gstin' => '27AABCT5678K1Z0',
        'business' => 'Distributor',
        'terms' => 45,
        'limit' => 200000
    ],
    [
        'code' => 'CL003',
        'name' => 'City Hospital Pharmacy',
        'phone' => '9988776655',
        'email' => 'pharmacy@cityhospital.com',
        'billing' => '123 Medical Complex, Pune',
        'shipping' => '123 Medical Complex, Pune',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'postal' => '411001',
        'gstin' => '27AABCT9012K1Z0',
        'business' => 'Hospital',
        'terms' => 60,
        'limit' => 100000
    ],
    [
        'code' => 'CL004',
        'name' => 'Dr. Sharma Clinic Pharmacy',
        'phone' => '9555444333',
        'email' => 'dr.sharma@clinic.com',
        'billing' => 'Clinic Building, Belgaum',
        'shipping' => 'Clinic Building, Belgaum',
        'city' => 'Belgaum',
        'state' => 'Karnataka',
        'postal' => '590001',
        'gstin' => null,
        'business' => 'Clinic',
        'terms' => 15,
        'limit' => 25000
    ]
];

foreach ($sampleClients as $client) {
    $stmt = $connect->prepare("
        INSERT INTO clients 
        (client_code, name, contact_phone, email, billing_address, shipping_address, 
         city, state, postal_code, gstin, business_type, payment_terms, credit_limit, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $status = 'ACTIVE';
    $stmt->bind_param(
        'ssssssssssddis',
        $client['code'],
        $client['name'],
        $client['phone'],
        $client['email'],
        $client['billing'],
        $client['shipping'],
        $client['city'],
        $client['state'],
        $client['postal'],
        $client['gstin'],
        $client['business'],
        $client['terms'],
        $client['limit'],
        $status
    );
    
    if ($stmt->execute()) {
        echo "✓ Added: {$client['name']}\n";
    } else {
        echo "✗ Error adding {$client['name']}: " . $stmt->error . "\n";
    }
}

echo "\n✓ Sample data loaded\n";
?>
