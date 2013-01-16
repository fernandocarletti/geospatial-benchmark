Geospatial Benchmark
====================

Files used to test geospatial search time between Solr 4, Postgres 9.2.2 + PostGIS and MySQL.

MySQL requires this function to work:

```SQL
DELIMITER //

CREATE function `giswithin` (pt POINT, mp MULTIPOLYGON) returns INT(1) DETERMINISTIC
begin
  DECLARE str, xy TEXT;
  DECLARE x, y, p1x, p1y, p2x, p2y, m, xinters DECIMAL(16, 13) DEFAULT 0;
  DECLARE counter INT DEFAULT 0;
  DECLARE p, pb, pe INT DEFAULT 0;

  SELECT Mbrwithin(pt, mp) INTO p;

  IF p != 1 OR Isnull(p) THEN
    RETURN p;
  end IF;

  SELECT X(pt), Y(pt), Astext(mp) INTO x, y, str;

  SET str = REPLACE(str, 'POLYGON((', '');
  SET str = REPLACE(str, '))', '');
  SET str = concat(str, ',');
  SET pb = 1;
  SET pe = locate(',', str);
  SET xy = substring(str, pb, pe - pb);
  SET p = instr(xy, ' ');
  SET p1x = substring(xy, 1, p - 1);
  SET p1y = substring(xy, p + 1);
  SET str = concat(str, xy, ',');

  WHILE pe > 0 do
    SET xy = substring(str, pb, pe - pb);
    SET p = instr(xy, ' ');
    SET p2x = substring(xy, 1, p - 1);
    SET p2y = substring(xy, p + 1);
    IF p1y < p2y THEN
      SET m = p1y;
    ELSE
      SET m = p2y;
    end IF;
    IF y > m THEN
      IF p1y > p2y THEN
        SET m = p1y;
      ELSE
        SET m = p2y;
      end IF;
      IF y <= m THEN
        IF p1x > p2x THEN
          SET m = p1x;
        ELSE
          SET m = p2x;
        end IF;
        IF x <= m THEN
          IF p1y != p2y THEN
            SET xinters = (y - p1y) * (p2x - p1x) / (p2y - p1y) + p1x;
          end IF;
          IF p1x = p2x
              OR x <= xinters THEN
            SET counter = counter + 1;
          end IF;
        end IF;
      end IF;
    end IF;
    SET p1x = p2x;
    SET p1y = p2y;
    SET pb = pe + 1;
    SET pe = locate(',', str, pb);
  end WHILE;

  RETURN counter % 2;
end;

DELIMITER ;
```