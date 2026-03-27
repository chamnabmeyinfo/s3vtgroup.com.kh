<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Database\Connection;

$db = Connection::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_migration'])) {
    try {
        $sqlFile = __DIR__ . '/../database/migrations/add_forklift_fields.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception('Migration file not found');
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $db->getPdo()->beginTransaction();
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $db->query($statement);
                } catch (Exception $e) {
                    // Ignore "column already exists" errors
                    if (strpos($e->getMessage(), 'Duplicate column name') === false && 
                        strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        $db->getPdo()->commit();
        $message = "Migration applied successfully! Added $successCount new fields. " . 
                   ($skippedCount > 0 ? "$skippedCount fields already existed and were skipped." : "");
        
    } catch (Exception $e) {
        if ($db->getPdo()->inTransaction()) {
            $db->getPdo()->rollBack();
        }
        $error = 'Error applying migration: ' . $e->getMessage();
    }
}

$pageTitle = 'Apply Forklift Fields Migration';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Apply Forklift Fields Migration</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Database Migration</h5>
                </div>
                <div class="card-body">
                    <p>This migration will add forklift-specific fields to the products table:</p>
                    <ul>
                        <li>Capacity, Lifting Height, Mast Type</li>
                        <li>Power Type, Engine Power, Battery Capacity</li>
                        <li>Fuel Consumption, Max Speed, Turning Radius</li>
                        <li>Dimensions (Length, Width, Height, Wheelbase)</li>
                        <li>Tire Type, Manufacturer Model, Year</li>
                        <li>Warranty Period, Country of Origin, Supplier URL</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <strong>Note:</strong> This migration is safe to run multiple times. It will skip columns that already exist.
                    </div>
                    
                    <form method="POST" action="">
                        <button type="submit" name="apply_migration" class="btn btn-primary">
                            <i class="fas fa-database"></i> Apply Migration
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

