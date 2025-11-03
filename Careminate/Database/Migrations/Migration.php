<?php 
namespace Careminate\Database\Migrations;

use PDO;
use Careminate\Models\Model;

abstract class Migration
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }
}