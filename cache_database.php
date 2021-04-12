<?php
define('MAX_CACHE_TIME', 86400); //24 hours in seconds
define('RECREATE_ON_FAIL', true);
define('CACHE_DATABASE', "cache.db");

class CacheDatabase extends SQLite3{
    private static $instance = null;

    private function __construct($path){
        try{
            parent::__construct($path);
            $this->exec("CREATE TABLE IF NOT EXISTS cache(id INTEGER PRIMARY KEY AUTOINCREMENT, url TEXT UNIQUE, epoch INTEGER, title TEXT, content BLOB, images TEXT)");
        }catch(Exception $ex){
            trigger_error("Error opening database: " . $ex->getMessage());
        }
    }

    public function getFromCache($url){
        if(!$statement = $this->prepare("SELECT content, title, images FROM cache WHERE url = ?")){
            return null;
        }
        $statement->bindParam(1, $url);
        if(!$result = $statement->execute()) return null;
        if(!$row = $result->fetchArray(SQLITE3_ASSOC)) return null;
        return array(
            0 => $row['title'],
            1 => $row['content'],
            2 => unserialize($row['images'])
        );
    }

    public function writeToCache($url, $title, $content, $images){
        if($cleanStatement = $this->prepare("DELETE FROM cache WHERE epoch < ?")){
            $cleanStatement->bindValue(1, time() - MAX_CACHE_TIME);
            $cleanStatement->execute();
        }
        if($statement = $this->prepare("INSERT INTO cache (url, epoch, title, content, images) VALUES (?, ?, ?, ?, ?)")){
            $statement->bindValue(1, $url);
            $statement->bindValue(2, time());
            $statement->bindValue(3, $title);
            $statement->bindValue(4, $content);
            $statement->bindValue(5, serialize($images));
            $statement->execute();
        }else{
            trigger_error("Cache database writing error");
            if(RECREATE_ON_FAIL){
                trigger_error("Deleting database");
                $this->close();
                unlink(CACHE_DATABASE);
                self::$instance = new CacheDatabase(CACHE_DATABASE);
            }
        }
    }

    public static function getInstance(){
        if(is_null(self::$instance)){
            self::$instance = new CacheDatabase(CACHE_DATABASE);
        }
        return self::$instance;
    }
}
?>
