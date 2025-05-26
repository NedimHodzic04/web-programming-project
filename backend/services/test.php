<?php
require_once 'UserService.php';
require_once 'ProductService.php';

$userService = new UserService();


try {
    echo "=== Getting user by email ===\n";
    $user = $userService->getUserByEmail('nedim@example.com');
    echo "✅ User found:\n";
    print_r($user);
    echo "\n";

} catch (Exception $e) {
    echo "❌ GetByEmail Error: " . $e->getMessage() . "\n\n";
}

try {
    echo "=== Updating user ===\n";
    $updated = $userService->updateUser($user['id'], [
        'first_name' => 'NedimUpdated',
        'last_name' => 'Hodzic',
        'email' => 'nedim@example.com', // email must match the one in DB
        'city' => 'Sarajevo',
        'address' => 'New Address',
        'zip' => '71001'
    ]);
    if ($updated) {
        echo "✅ Update successful\n";
        $updatedUser = $userService->getUserByEmail('nedim@example.com');
        print_r($updatedUser);
    } else {
        echo "❌ Update failed\n";
    }

} catch (Exception $e) {
    echo "❌ Update Error: " . $e->getMessage() . "\n\n";
}
