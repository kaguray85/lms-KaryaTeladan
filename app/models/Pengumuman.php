<?php
class Pengumuman
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where=[]; $params=[];
        if(!empty($filters['target_role'])){ $where[]='p.target_role=:target_role'; $params[':target_role']=$filters['target_role']; }
        if(!empty($filters['role_view'])){ $where[]='(p.target_role = :role_view OR p.target_role = "all")'; $params[':role_view']=$filters['role_view']; }
        if(!empty($filters['search'])){ $where[]='(p.judul LIKE :search OR p.isi LIKE :search OR u.name LIKE :search)'; $params[':search']='%'.$filters['search'].'%'; }
        $sql='SELECT p.*, u.name AS pembuat, u.role AS pembuat_role FROM pengumuman p INNER JOIN users u ON u.id=p.user_id';
        if($where){$sql.=' WHERE '.implode(' AND ',$where);} $sql.=' ORDER BY p.tanggal DESC, p.created_at DESC';
        $stmt=$db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public static function findById(PDO $db, int $id): ?array
    { $stmt=$db->prepare('SELECT * FROM pengumuman WHERE id=:id LIMIT 1'); $stmt->execute([':id'=>$id]); $row=$stmt->fetch(); return $row?:null; }
    public static function create(PDO $db, array $payload): int
    { $stmt=$db->prepare('INSERT INTO pengumuman (user_id, judul, isi, target_role, tanggal, created_at, updated_at) VALUES (:user_id,:judul,:isi,:target_role,:tanggal,NOW(),NOW())'); $stmt->execute([':user_id'=>$payload['user_id'], ':judul'=>$payload['judul'], ':isi'=>$payload['isi'], ':target_role'=>$payload['target_role'], ':tanggal'=>$payload['tanggal']]); return (int)$db->lastInsertId(); }
    public static function update(PDO $db, int $id, array $payload): void
    { $stmt=$db->prepare('UPDATE pengumuman SET judul=:judul, isi=:isi, target_role=:target_role, tanggal=:tanggal, updated_at=NOW() WHERE id=:id'); $stmt->execute([':id'=>$id, ':judul'=>$payload['judul'], ':isi'=>$payload['isi'], ':target_role'=>$payload['target_role'], ':tanggal'=>$payload['tanggal']]); }
    public static function delete(PDO $db, int $id): void
    { $stmt=$db->prepare('DELETE FROM pengumuman WHERE id=:id'); $stmt->execute([':id'=>$id]); }
}
