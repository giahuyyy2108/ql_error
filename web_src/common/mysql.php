<?php
class db_mysql
{
    var $db_host = "";
    var $db_name = "";
    var $db_username = "";
    var $db_password = "";

    var $serverconn = 0;
    var $dbconn = 0;

    var $query_id = 0;
    var $result = array();
    var $query_count = 0;
    var $insert_id = 0;

    function __construct()
    {
        global $db_host, $db_name, $db_username, $db_password;
        if (!isset($db_host) || !isset($db_name) || !isset($db_username) || !isset($db_password)) {
            $this->halt("Not exist database's information: database host, database name,...");
        }
        $this->db_host = $db_host;
        $this->db_name = $db_name;
        $this->db_username = $db_username;
        $this->db_password = $db_password;
    }

    function connect()
    {
        global $connect;
        if ($connect == "") {
            /*if ( $this->db_password=="" ){
                        $this->serverconn = mysqli_connect($this->db_host,$this->db_username)  or  $this->halt("Couldn't connect to database.");
                        }
                        else{*/
            $this->serverconn = mysqli_init();

            //specify the connection timeout
            $this->serverconn->options(MYSQLI_OPT_CONNECT_TIMEOUT, _DATABASE_CONNECT_TIMEOUT_);

            //specify the read timeout
            $this->serverconn->options(MYSQLI_OPT_READ_TIMEOUT, _DATABASE_READ_TIMEOUT_);

            $this->serverconn->real_connect($this->db_host, $this->db_username, $this->db_password, $this->db_name) or $this->halt("Couldn't connect to database.");
            //$this->serverconn = new mysqli($this->db_host,$this->db_username,$this->db_password, $this->db_name)  or  $this->halt("Couldn't connect to database.");
            //}
            //mysql_query("SET CHARACTER SET 'UTF8'");
            //mysql_query("SET NAMES 'utf8'");
            $this->serverconn->character_set_name();
            $connect = $this->serverconn;
        }
    }

    function selectdb($database = "")
    {
        global $connect;
        if ($connect == "") {
            if ($database != "")
                $this->db_name = $database;
            //$this->dbconn = mysql_select_db($this->db_name,$this->serverconn)  or  $this->halt("Couldn't use this database:". $this->db_name);
            $this->dbconn = $this->serverconn->select_db($this->db_name) or $this->halt("Couldn't use this database:" . $this->db_name);
        }
    }

    function query($query_string = "")
    {
        global $connect;
        if ($query_string != "") {
            $this->query_id = $connect->query($query_string) or $this->halt("Khong the thuc hien cau TRUY VAN: $query_string");
            $this->query_count++;

        }
        return $this->query_id;
    }

    function fetch_array($query_id = null)
    {
        if ($query_id != null)
            $this->query_id = $query_id;
        $this->result = mysqli_fetch_array($this->query_id, MYSQLI_BOTH);
        return $this->result;
    }

    function num_rows($query_id = -1)
    {
        if ($query_id != -1)
            $this->query_id = $query_id;
        return mysqli_num_rows($this->query_id);
    }

    function check_exist($fromtable, $checkname, $checkvalue)
    {
        $sql_query = "SELECT * FROM $fromtable WHERE $checkname='" . $checkvalue . "'";
        $this->query($sql_query);
        if ($this->num_rows() > 0)
            return 1;

        return 0;
    }

    function insert_id()
    {
        global $connect;
        $this->insert_id = $connect->insert_id;
        return $this->insert_id;
    }

    function close()
    {
        global $connect;
        return $connect->close($this->serverconn);
    }

    function halt($msg)
    {
        echo "<center><b>ERROR</b></center><br/>";
        echo $msg;
        echo "L&#7895;i khi th&#7921;c thi c&acirc;u l&#7879;nh SQL. Xin b&aacute;o cho admin bi&#7871;t v&#7873; l&#7895;i n&agrave;y. Xin c&#7843;m &#417;n!<br><a href='javascript:history.back(-1);'>Back</a>";
        die();
    }
}

?>