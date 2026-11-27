<?php
/**
 * Wrap an SVN username in an HTML container showing the user's real name
 * (from the USVN users table) as a tooltip on hover.
 *
 * @author Team USVN <contact@usvn.info>
 * @package helper
 */
class USVN_View_Helper_SvnUser {
    public function svnUser($login)
    {
        $login = (string)$login;
        $escaped = htmlspecialchars($login, ENT_QUOTES, 'UTF-8');
        $fullname = '';
        if (strlen(trim($login))) {
            try {
                $table = new USVN_Db_Table_Users();
                $user = $table->fetchRow(array('users_login = ?' => $login));
                if ($user !== null) {
                    $fullname = trim(trim($user->lastname) . ' ' . trim($user->firstname));
                }
            } catch (Exception $e) {
                $fullname = '';
            }
        }
        if (strlen($fullname)) {
            $title = htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8');
            return '<span class="svn_user" title="' . $title . '">' . $escaped . '</span>';
        }
        return '<span class="svn_user">' . $escaped . '</span>';
    }
}
