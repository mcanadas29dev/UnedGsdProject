# Base de datos

## Tablas principales

### product
- id: int
- name: string
- price: float
- category_id: FK → category

### category
- id: int
- name: string
- parent_id: FK → category (nullable)
