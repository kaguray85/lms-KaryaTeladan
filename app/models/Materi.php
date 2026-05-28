<?php
class Materi
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where=[]; $params=[];
        if (!empty($filters['guru_id'])) { $where[]='mt.guru_id=:guru_id'; $params[':guru_id']=(int)$filters['guru_id']; }
        if (!empty($filters['kelas_id'])) { $where[]='mt.kelas_id=:kelas_id'; $params[':kelas_id']=(int)$filters['kelas_id']; }
        if (!empty($filters['mapel_id'])) { $where[]='mt.mapel_id=:mapel_id'; $params[':mapel_id']=(int)$filters['mapel_id']; }
        if (!empty($filters['search'])) { $where[]='(mt.judul_materi LIKE :search OR mp.nama_mapel LIKE :search OR k.nama_kelas LIKE :search OR g.nama_guru LIKE :search)'; $params[':search']='%'.$filters['search'].'%'; }
        $sql="SELECT mt.*, g.nama_guru, k.nama_kelas, k.jurusan, mp.nama_mapel
              FROM materi mt
              INNER JOIN guru g ON g.id=mt.guru_id
              INNER JOIN kelas k ON k.id=mt.kelas_id
              INNER JOIN mata_pelajaran mp ON mp.id=mt.mapel_id";
        if($where){$sql.=' WHERE '.implode(' AND ',$where);} $sql.=' ORDER BY mt.tanggal_upload DESC, mt.created_at DESC';
        $stmt=$db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public static function findById(PDO $db, int $id): ?array
    { $stmt=$db->prepare('SELECT * FROM materi WHERE id=:id LIMIT 1'); $stmt->execute([':id'=>$id]); $row=$stmt->fetch(); return $row?:null; }

    public static function create(PDO $db, array $payload): int
    {
        $stmt=$db->prepare('INSERT INTO materi (guru_id, kelas_id, mapel_id, judul_materi, deskripsi, file_materi, tanggal_upload, created_at, updated_at)
                            VALUES (:guru_id,:kelas_id,:mapel_id,:judul_materi,:deskripsi,:file_materi,:tanggal_upload,NOW(),NOW())');
        $stmt->execute([':guru_id'=>$payload['guru_id'], ':kelas_id'=>$payload['kelas_id'], ':mapel_id'=>$payload['mapel_id'], ':judul_materi'=>$payload['judul_materi'], ':deskripsi'=>$payload['deskripsi']?:null, ':file_materi'=>$payload['file_materi']?:null, ':tanggal_upload'=>$payload['tanggal_upload']]);
        return (int)$db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $payload): void
    {
        $old=self::findById($db,$id); if(!$old){throw new RuntimeException('Data materi tidak ditemukan.');}
        $file=$payload['file_materi'] ?: $old['file_materi'];
        $stmt=$db->prepare('UPDATE materi SET kelas_id=:kelas_id,mapel_id=:mapel_id,judul_materi=:judul_materi,deskripsi=:deskripsi,file_materi=:file_materi,tanggal_upload=:tanggal_upload,updated_at=NOW() WHERE id=:id AND guru_id=:guru_id');
        $stmt->execute([':id'=>$id, ':guru_id'=>$payload['guru_id'], ':kelas_id'=>$payload['kelas_id'], ':mapel_id'=>$payload['mapel_id'], ':judul_materi'=>$payload['judul_materi'], ':deskripsi'=>$payload['deskripsi']?:null, ':file_materi'=>$file?:null, ':tanggal_upload'=>$payload['tanggal_upload']]);
    }

    public static function delete(PDO $db, int $id, int $guruId): void
    { $stmt=$db->prepare('DELETE FROM materi WHERE id=:id AND guru_id=:guru_id'); $stmt->execute([':id'=>$id, ':guru_id'=>$guruId]); }
}
