---
trigger: manual
---

Si hay migraciones nuevas sobre una tabla existente solo encargate de hacer alter table en las migraciones y no un create table con ese nuevo campo que te estoy pidiendo.

Recuerda que este proyecto es multitenancy, hay tenant_id y branch_id