<?php
/**
 * wp-theme-tool
 * PHP Script that shows all themes installed in a WordPress instance (plus 
 * network if is enabled). Very useful for system admins.
 *
 * @author    Erick Belluci Tedeschi <erick@oerick.com>
 * @example   php themes.php /path/to/wordpress/wp-config.php
 *
 */


class Themes {

    private $conn = null;
    private $table_prefix = 'wp_';
    private $blogs = array();

    public function __construct(array $config) {
        if (array_key_exists('host', $config) && 
            array_key_exists('user', $config) &&
            array_key_exists('pass', $config) &&
            array_key_exists('db', $config)) {

                if (array_key_exists('prefix', $config)) {
                    $this->table_prefix = $config['prefix'];//mysql_real_escape_string($config['prefix']);
                } else {
                    $this->table_prefix = 'wp_';
                }

                $host = $config['host'];
                $user = $config['user'];
                $pass = $config['pass'];
                $db   = $config['db'];

                $this->conn = new mysqli($host, $user, $pass, $db);
                if ($this->conn->connect_error) {
                    die('Connection error (' . $this->conn->connect_errno() . ') ' . $this->conn->connect_error());
                }
            }
    }

    public function getBlogs() {
        if ($res = $this->conn->query("SELECT count(*) as qtd FROM information_schema.tables WHERE table_name = '{$this->table_prefix}blogs'")) {
            $tmp = $res->fetch_row();
            if ($tmp[0] > (int)0) {
                if ($result = $this->conn->query("SELECT * FROM {$this->table_prefix}blogs")) {
                    //$blogs = $result->fetch_all(MYSQLI_ASSOC);
                    while ($row = $result->fetch_assoc()) {
                        $blogs[] = $row;
                    }

                    $this->blogs = $blogs;
                    $result->close();
                }
            } else {
                $this->blogs[] = array(
                    'blog_id' => 1,
                    'domain'=> 'singledomain',
                    'path' => '/'
                );
            }

        }
    }


    public function getThemes() {
        echo "_______ Temas disponiveis na rede ________\r\n";
        if ($result = $this->conn->query("SELECT meta_value FROM {$this->table_prefix}sitemeta WHERE meta_key = '_site_transient_theme_roots' LIMIT 1")) {
            $value = $result->fetch_assoc();
            print_r(unserialize($value['meta_value']));
            //print_r(array_keys(unserialize($value['meta_value'])));
            $result->close();
        }

        foreach ($this->blogs as $blog) {
            echo "---- Blog {$blog['blog_id']}: {$blog['domain']}{$blog['path']}\r\n";
            $table_name = ($blog['blog_id'] == '1') ? $this->table_prefix . 'options' : $this->table_prefix . $blog['blog_id'] . '_options';
            if ($result = $this->conn->query("SELECT option_value FROM {$table_name} WHERE option_name = 'template' LIMIT 1")) {
                $tmpvalue = $result->fetch_assoc();
                echo "\t" . $tmpvalue['option_value'] . "\r\n\r\n";
                $result->close();
            } else {
                echo "Opcao 'template' nao encontrada na tabela {$table_name}\r\n";
            }
        }

    }

}

if ($argc == 2) {
    if (is_readable($argv[1])) {
        $wpconfig = file_get_contents($argv[1]);
        preg_match_all('/define\(\'DB_(USER|PASSWORD|NAME|HOST)\',\ \'(.*)\'\)/', $wpconfig, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            switch ($match[1]) {
                case 'NAME': $config['db'] = $match[2]; break;
                case 'USER': $config['user'] = $match[2]; break;
                case 'PASSWORD': $config['pass'] = $match[2]; break;
                case 'HOST': $config['host'] = $match[2]; break;
            }
        }
        preg_match_all('/\$table_prefix\s+\=\s+[\'"]([a-zA-Z0-9_]+)[\'"]/', $wpconfig, $matcht, PREG_SET_ORDER);
        if (count($matcht) >= 1) {
            $tmp = end($matcht);
            $config['prefix'] = $tmp[1];
        } else {
            print_r($matcht);
            die('$table_prefix nao encontrado no arquivo de configuracao informado');
        }
    } else {
        die('Arquivo de configuracao informado nao eh possivel ser lido');
    }
} else {
   echo "Modo de usar: \r\n";
   echo "  $ php themes.php wp-config.php\r\n";
   exit();
}

if (true) {
    echo "Debug: Informacoes usadas para conectar no DB\r\n";
    print_r($config);
}

$t = new Themes($config);
$t->getBlogs();
$t->getThemes();

