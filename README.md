# Como borrar registros antinguos de auditoria
# P6M = logs de más de 6 meses. Podés poner P1Y (1 año), P30D, etc. Para más formatos disponibles, ver: https://www.php.net/manual/en/dateinterval.construct.php
```bash

php bin/console audit-logs:delete-old-logs --retention-period=P6M

```