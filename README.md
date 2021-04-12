# 68k-news
Source for the 68k.news site

---

I added an SQLite3 cache database in which the articles are stored for 24h. After that period they automatically get deleted.

The database can be disabled by changing the `USE_CACHE` define in `article.php`.

If the database file becomes corrupted, the program will try to delete and recreate it. (This can be turned off by setting `RECREATE_ON_FAIL` to `false` in `cache_database.php`).

The default cache freshness lifetime is 24h, however it can be changed using the `MAX_CACHE_TIME` parameter in `cache_database.php` along with the default cache database filename (`cache.db`).

I also had to add a spoofed User-Agent header, because some articles couldn't load without it.
