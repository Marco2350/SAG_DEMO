<?php
class ExportarController extends Controller
{
    public function __construct()
    {
        $this->requirePrograma();
    }

    public function index(): void
    {
        $db            = Database::programa();
        $pid           = Database::proyectoId();
        $departamentos = $db->fetchAll(
            "SELECT id_departamento, nombre FROM sag_departamentos WHERE activo=1 AND id_proyecto=? ORDER BY nombre",
            [$pid]
        );

        // Conteos para tarjetas de módulo
        $conteos = [
            'beneficiarios'   => (int) ($db->fetchOne("SELECT COUNT(*) AS c FROM sag_beneficiarios WHERE id_proyecto=?", [$pid])['c'] ?? 0),
            'organizaciones'  => (int) ($db->fetchOne("SELECT COUNT(*) AS c FROM sag_organizaciones WHERE id_proyecto=?", [$pid])['c'] ?? 0),
            'capacitaciones'  => (int) ($db->fetchOne("SELECT COUNT(*) AS c FROM sag_capacitaciones WHERE id_proyecto=?", [$pid])['c'] ?? 0),
            'participantes'   => (int) ($db->fetchOne("SELECT COALESCE(SUM(num_participantes),0) AS c FROM sag_capacitaciones WHERE id_proyecto=?", [$pid])['c'] ?? 0),
            'asistencias'     => (int) ($db->fetchOne("SELECT COUNT(*) AS c FROM sag_asistencias_tecnicas WHERE id_proyecto=?", [$pid])['c'] ?? 0),
        ];

        $pageTitle = 'Exportar Datos — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('exportar/index', compact('pageTitle', 'departamentos', 'conteos'));
    }

    public function generar(): void
    {
        $modulo  = $this->getPost('modulo', '');
        $formato = $this->getPost('formato', 'csv');

        $modulos = ['beneficiarios', 'organizaciones', 'capacitaciones', 'asistencias'];
        if (!in_array($modulo, $modulos)) {
            $this->error('Módulo no válido.');
            return;
        }

        try {
            $db     = Database::programa();
            $filtros = [
                'anio'            => (int)  $this->getPost('anio', 0),
                'id_departamento' => (int)  $this->getPost('id_departamento', 0),
                'estado'          => $this->getPost('estado', ''),
            ];

            $rows     = $this->getData($db, $modulo, $filtros);
            $filename = 'SAG_' . strtoupper($modulo) . '_' . date('Ymd_His') . '.csv';

            // Stream CSV
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // BOM para Excel
            echo "\xEF\xBB\xBF";

            $out = fopen('php://output', 'w');

            if (!empty($rows)) {
                fputcsv($out, array_keys($rows[0]), ';');
                foreach ($rows as $row) {
                    fputcsv($out, $row, ';');
                }
            }

            fclose($out);
            exit;

        } catch (Exception $e) {
            error_log('ExportarController::generar — ' . $e->getMessage());
            $this->error('Error al generar el archivo: ' . $e->getMessage());
        }
    }

    private function getData($db, string $modulo, array $f): array
    {
        $where  = ['1=1'];
        $params = [];
        $pid    = Database::proyectoId();

        switch ($modulo) {

            case 'beneficiarios':
                $where[] = 'b.id_proyecto=?'; $params[] = $pid;
                if ($f['id_departamento']) { $where[] = 'b.id_departamento=?'; $params[] = $f['id_departamento']; }
                if ($f['estado'])          { $where[] = 'b.estado=?';          $params[] = $f['estado']; }
                if ($f['anio'])            { $where[] = 'YEAR(b.created_at)=?';$params[] = $f['anio']; }
                return $db->fetchAll(
                    "SELECT b.id_beneficiario AS 'ID',
                            CONCAT(b.nombre,' ',b.apellido) AS 'Nombre Completo',
                            b.dni AS 'DNI', b.sexo AS 'Sexo',
                            b.fecha_nacimiento AS 'Fecha Nacimiento',
                            b.telefono AS 'Teléfono', b.email AS 'Email',
                            d.nombre AS 'Departamento', m.nombre AS 'Municipio',
                            b.aldea AS 'Aldea',
                            o.nombre AS 'Organización',
                            b.cultivo_principal AS 'Cultivo Principal',
                            b.area_productiva AS 'Área Productiva (Ha)',
                            b.estado AS 'Estado',
                            b.created_at AS 'Fecha Registro'
                     FROM sag_beneficiarios b
                     LEFT JOIN sag_departamentos d ON d.id_departamento=b.id_departamento
                     LEFT JOIN sag_municipios m ON m.id_municipio=b.id_municipio
                     LEFT JOIN sag_organizaciones o ON o.id_organizacion=b.id_organizacion
                     WHERE " . implode(' AND ', $where) . " ORDER BY b.apellido, b.nombre",
                    $params
                );

            case 'organizaciones':
                $where[] = 'o.id_proyecto=?'; $params[] = $pid;
                if ($f['id_departamento']) { $where[] = 'o.id_departamento=?'; $params[] = $f['id_departamento']; }
                if ($f['estado'])          { $where[] = 'o.estado=?';          $params[] = $f['estado']; }
                return $db->fetchAll(
                    "SELECT o.id_organizacion AS 'ID',
                            o.nombre AS 'Nombre',
                            o.tipo AS 'Tipo',
                            d.nombre AS 'Departamento', m.nombre AS 'Municipio',
                            o.aldea AS 'Aldea',
                            o.representante AS 'Representante',
                            o.telefono AS 'Teléfono', o.email AS 'Email',
                            o.estado AS 'Estado',
                            o.fecha_registro AS 'Fecha Registro',
                            (SELECT COUNT(*) FROM sag_beneficiarios b
                             WHERE b.id_organizacion=o.id_organizacion AND b.estado='activo') AS 'Beneficiarios Activos'
                     FROM sag_organizaciones o
                     LEFT JOIN sag_departamentos d ON d.id_departamento=o.id_departamento
                     LEFT JOIN sag_municipios m ON m.id_municipio=o.id_municipio
                     WHERE " . implode(' AND ', $where) . " ORDER BY o.nombre",
                    $params
                );

            case 'capacitaciones':
                $where[] = 'c.id_proyecto=?'; $params[] = $pid;
                if ($f['id_departamento']) { $where[] = 'c.id_departamento=?'; $params[] = $f['id_departamento']; }
                if ($f['anio'])            { $where[] = 'YEAR(c.fecha_capacitacion)=?'; $params[] = $f['anio']; }
                if ($f['estado'])          { $where[] = 'c.estado=?'; $params[] = $f['estado']; }
                return $db->fetchAll(
                    "SELECT c.id_capacitacion AS 'ID',
                            c.fecha_capacitacion AS 'Fecha',
                            t.nombre AS 'Tema',
                            s.nombre AS 'Subtema',
                            d.nombre AS 'Departamento', m.nombre AS 'Municipio',
                            c.aldea AS 'Aldea', c.lugar_especifico AS 'Lugar',
                            CONCAT(te.nombre_completo) AS 'Técnico',
                            c.num_participantes AS 'Participantes',
                            c.duracion_horas AS 'Duración (hrs)',
                            c.estado AS 'Estado'
                     FROM sag_capacitaciones c
                     LEFT JOIN sag_temas t ON t.id_tema=c.id_tema
                     LEFT JOIN sag_subtemas s ON s.id_subtema=c.id_subtema
                     LEFT JOIN sag_departamentos d ON d.id_departamento=c.id_departamento
                     LEFT JOIN sag_municipios m ON m.id_municipio=c.id_municipio
                     LEFT JOIN sag_tecnicos te ON te.id_tecnico=c.id_tecnico
                     WHERE " . implode(' AND ', $where) . " ORDER BY c.fecha_capacitacion DESC",
                    $params
                );

            case 'asistencias':
                $where[] = 'a.id_proyecto=?'; $params[] = $pid;
                if ($f['id_departamento']) { $where[] = 'a.id_departamento=?'; $params[] = $f['id_departamento']; }
                if ($f['anio'])            { $where[] = 'YEAR(a.fecha_visita)=?'; $params[] = $f['anio']; }
                if ($f['estado'])          { $where[] = 'a.estado=?'; $params[] = $f['estado']; }
                return $db->fetchAll(
                    "SELECT a.id_at AS 'ID',
                            a.fecha_visita AS 'Fecha',
                            ta.nombre AS 'Tipo AT',
                            t.nombre AS 'Tema',
                            s.nombre AS 'Subtema',
                            cu.nombre AS 'Cultivo',
                            d.nombre AS 'Departamento', m.nombre AS 'Municipio',
                            a.aldea AS 'Aldea',
                            CONCAT(a.productor_nombre,' ',COALESCE(a.productor_apellido,'')) AS 'Productor',
                            a.productor_dni AS 'DNI Productor',
                            a.productor_sexo AS 'Sexo',
                            a.productor_telefono AS 'Teléfono',
                            te.nombre_completo AS 'Técnico',
                            a.area_productiva AS 'Área (Ha)',
                            a.estado AS 'Estado'
                     FROM sag_asistencias_tecnicas a
                     LEFT JOIN sag_tipo_at ta ON ta.id_tipo_at=a.id_tipo_at
                     LEFT JOIN sag_temas t ON t.id_tema=a.id_tema
                     LEFT JOIN sag_subtemas s ON s.id_subtema=a.id_subtema
                     LEFT JOIN sag_cultivos cu ON cu.id_cultivo=a.id_cultivo
                     LEFT JOIN sag_departamentos d ON d.id_departamento=a.id_departamento
                     LEFT JOIN sag_municipios m ON m.id_municipio=a.id_municipio
                     LEFT JOIN sag_tecnicos te ON te.id_tecnico=a.id_tecnico
                     WHERE " . implode(' AND ', $where) . " ORDER BY a.fecha_visita DESC",
                    $params
                );
        }
        return [];
    }
}
