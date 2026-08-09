<?php
/**
 * mysqli_stmt_get_result() solo existe con el driver mysqlnd.
 * Sin mysqlnd, obtener filas asociativas tras execute() requiere bind_result.
 */
if (!function_exists('camera_mysqli_stmt_fetch_assoc')) {
    /**
     * Una fila como array asociativo tras mysqli_stmt_execute(), o null.
     * Sin tipo nullable (?array): compatible con PHP 7.0 (7.1+ añadió tipos nullables).
     *
     * @param mysqli_stmt $stmt
     * @return array|null
     */
    function camera_mysqli_stmt_fetch_assoc($stmt)
    {
        if (function_exists('mysqli_stmt_get_result')) {
            $r = @mysqli_stmt_get_result($stmt);
            if ($r instanceof mysqli_result) {
                $row = mysqli_fetch_assoc($r);
                mysqli_free_result($r);

                return $row ?: null;
            }
        }

        if (!mysqli_stmt_store_result($stmt)) {
            return null;
        }

        $meta = mysqli_stmt_result_metadata($stmt);
        if (!$meta) {
            return null;
        }

        $row = array();
        $bind = array();
        while ($field = mysqli_fetch_field($meta)) {
            $name = $field->name;
            $row[$name] = null;
            $bind[] = &$row[$name];
        }
        mysqli_free_result($meta);

        if (empty($bind)) {
            return null;
        }

        call_user_func_array(array($stmt, 'bind_result'), $bind);

        if (!mysqli_stmt_fetch($stmt)) {
            return null;
        }

        return $row;
    }
}
