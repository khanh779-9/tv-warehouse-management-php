# MySQL template database - TV warehouse

`data.sql` is a complete MySQL 8.x bootstrap script. It creates `warehouse_portfolio`, all tables/indexes/foreign keys, and TV-only warehouse/distribution seed data.

```bash
mysql -u root -p < data.sql
```

Seeded accounts all use password `password`:

- `admin@warehouse.local`
- `manager@warehouse.local`
- `staff@warehouse.local`
- `viewer@warehouse.local`

The dataset uses fictional TV brands/models and demonstrates per-TV serial tracking, TV specification fields, On Hand vs Reserved vs Available inventory, manager approvals, dealer orders, returns, warranty/service traceability and warehouse locations.
