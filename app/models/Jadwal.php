<?php
class Jadwal
{
    public static function all(PDO $db, array $filters = []): array
    {
        $where = [];
        $params = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $terms = preg_split('/\s+/', $search) ?: [];
            foreach ($terms as $index => $term) {
                if ($term === '') {
                    continue;
                }

                $key = ':search_' . $index;
                $where[] = "(j.hari LIKE {$key}
                    OR 'jadwal' LIKE {$key}
                    OR 'jadwal pelajaran' LIKE {$key}
                    OR 'kelas' LIKE {$key}
                    OR 'mapel' LIKE {$key}
                    OR 'mata pelajaran' LIKE {$key}
                    OR TIME_FORMAT(j.jam_mulai, '%H:%i') LIKE {$key}
                    OR TIME_FORMAT(j.jam_selesai, '%H:%i') LIKE {$key}
                    OR j.ruangan LIKE {$key}
                    OR j.status LIKE {$key}
                    OR CASE j.status WHEN 'active' THEN 'Aktif' ELSE 'Nonaktif' END LIKE {$key}
                    OR k.nama_kelas LIKE {$key}
                    OR k.jurusan LIKE {$key}
                    OR mp.kode_mapel LIKE {$key}
                    OR mp.nama_mapel LIKE {$key}
                    OR mp.semester LIKE {$key}
                    OR g.nama_guru LIKE {$key})";
                $params[$key] = '%' . $term . '%';
            }
        }

        $status = $filters['status'] ?? '';
        if ($status !== '') {
            $where[] = 'j.status = :status';
            $params[':status'] = $status;
        }

        $hari = $filters['hari'] ?? '';
        if ($hari !== '') {
            $where[] = 'j.hari = :hari';
            $params[':hari'] = $hari;
        }

        $kelasId = $filters['kelas_id'] ?? '';
        if ($kelasId !== '') {
            $where[] = 'j.kelas_id = :kelas_id';
            $params[':kelas_id'] = (int) $kelasId;
        }

        $guruId = $filters['guru_id'] ?? '';
        if ($guruId !== '') {
            $where[] = 'j.guru_id = :guru_id';
            $params[':guru_id'] = (int) $guruId;
        }

        $mapelId = $filters['mapel_id'] ?? '';
        if ($mapelId !== '') {
            $where[] = 'j.mapel_id = :mapel_id';
            $params[':mapel_id'] = (int) $mapelId;
        }

        $sql = "SELECT
                    j.id,
                    j.hari,
                    TIME_FORMAT(j.jam_mulai, '%H:%i') AS jam_mulai,
                    TIME_FORMAT(j.jam_selesai, '%H:%i') AS jam_selesai,
                    j.kelas_id,
                    k.nama_kelas,
                    k.jurusan,
                    j.mapel_id,
                    mp.kode_mapel,
                    mp.nama_mapel,
                    j.guru_id,
                    g.nama_guru,
                    j.ruangan,
                    j.status,
                    j.created_at,
                    j.updated_at
                FROM jadwal_pelajaran j
                INNER JOIN kelas k ON k.id = j.kelas_id
                INNER JOIN mata_pelajaran mp ON mp.id = j.mapel_id
                INNER JOIN guru g ON g.id = j.guru_id";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai ASC, k.nama_kelas ASC";

        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $statement = $db->prepare('SELECT * FROM jadwal_pelajaran WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $jadwal = $statement->fetch();

        return $jadwal ?: null;
    }

    public static function hasConflict(PDO $db, array $payload, ?int $excludeId = null): bool
    {
        $sql = "SELECT id
                FROM jadwal_pelajaran
                WHERE hari = :hari
                  AND status = 'active'
                  AND (:jam_mulai < jam_selesai AND :jam_selesai > jam_mulai)
                  AND (kelas_id = :kelas_id OR guru_id = :guru_id OR ruangan = :ruangan)";
        $params = [
            ':hari' => $payload['hari'],
            ':jam_mulai' => $payload['jam_mulai'],
            ':jam_selesai' => $payload['jam_selesai'],
            ':kelas_id' => $payload['kelas_id'],
            ':guru_id' => $payload['guru_id'],
            ':ruangan' => $payload['ruangan'],
        ];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetch();
    }

    public static function create(PDO $db, array $payload): int
    {
        $statement = $db->prepare(
            'INSERT INTO jadwal_pelajaran (hari, jam_mulai, jam_selesai, kelas_id, mapel_id, guru_id, ruangan, status, created_at, updated_at)
             VALUES (:hari, :jam_mulai, :jam_selesai, :kelas_id, :mapel_id, :guru_id, :ruangan, :status, NOW(), NOW())'
        );

        $statement->execute([
            ':hari' => $payload['hari'],
            ':jam_mulai' => $payload['jam_mulai'],
            ':jam_selesai' => $payload['jam_selesai'],
            ':kelas_id' => $payload['kelas_id'],
            ':mapel_id' => $payload['mapel_id'],
            ':guru_id' => $payload['guru_id'],
            ':ruangan' => $payload['ruangan'],
            ':status' => $payload['status'],
        ]);

        return (int) $db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $payload): void
    {
        $statement = $db->prepare(
            'UPDATE jadwal_pelajaran
             SET hari = :hari,
                 jam_mulai = :jam_mulai,
                 jam_selesai = :jam_selesai,
                 kelas_id = :kelas_id,
                 mapel_id = :mapel_id,
                 guru_id = :guru_id,
                 ruangan = :ruangan,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            ':id' => $id,
            ':hari' => $payload['hari'],
            ':jam_mulai' => $payload['jam_mulai'],
            ':jam_selesai' => $payload['jam_selesai'],
            ':kelas_id' => $payload['kelas_id'],
            ':mapel_id' => $payload['mapel_id'],
            ':guru_id' => $payload['guru_id'],
            ':ruangan' => $payload['ruangan'],
            ':status' => $payload['status'],
        ]);
    }

    public static function softDelete(PDO $db, int $id): void
    {
        $statement = $db->prepare("UPDATE jadwal_pelajaran SET status = 'inactive', updated_at = NOW() WHERE id = :id");
        $statement->execute([':id' => $id]);
    }
}
