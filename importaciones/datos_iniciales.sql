-- ================================================================
-- Datos iniciales: Importaciones históricas
-- Ejecutar en phpMyAdmin DESPUÉS de correr migrar.php
-- ================================================================

-- 1. Forwarders
INSERT INTO forwarders (nombre) VALUES ('Locksley');
SET @locksley = LAST_INSERT_ID();
INSERT INTO forwarders (nombre) VALUES ('Transcargo');
SET @transcargo = LAST_INSERT_ID();

-- 2. Importaciones (21 filas)
INSERT INTO importaciones
  (proveedor, origen, familia_productos, numero_proforma, monto_fob, etd, eta, numero_bl, nombre_barco, forwarder_id, observaciones, estado)
VALUES
  ('Zodo Tire Co',       'CHINA',    'Neumáticos', 'LY25030175',    28572.22,  '2025-05-05', '2025-07-24', NULL,            NULL,               NULL,        'Neumaticos Landgema',  'arribado'),
  ('Rotary lift Haimen', 'CHINA',    'Máquinas',   'TR01-2025',     42291.00,  '2025-06-15', '2025-08-27', 'SY250662081',   'EVER FEAT',        @locksley,   'SPOA10 Y SM14',        'arribado'),
  ('GUANGZHOU JINGJIA',  'CHINA',    'Máquinas',   'A9AR0525001',   43373.15,  NULL,          '2025-07-24', NULL,            NULL,               NULL,        'DOS COLUMNAS, CABLES', 'arribado'),
  ('GUANGZHOU JINGJIA',  'CHINA',    'Máquinas',   'A9AR0525002',   41726.24,  '2025-07-22', '2025-09-07', 'SY250764403',   'KOTA PUSAKA',      @locksley,   'JULIO',                'arribado'),
  ('GUANGZHOU JINGJIA',  'CHINA',    'Máquinas',   'A9AR0525003',   53063.46,  '2025-11-05', '2025-12-26', NULL,            'CMA CGM ZINGARO',  @locksley,   NULL,                   'arribado'),
  ('GUANGZHOU JINGJIA',  'CHINA',    'Máquinas',   'A9AR0525004',   42647.40,  '2025-09-17', '2025-11-05', NULL,            'KOTA EBONY',       @locksley,   NULL,                   'arribado'),
  ('GUANGZHOU JINGJIA',  'CHINA',    'Máquinas',   'A9AR0525005',   38934.56,  '2025-11-17', '2026-01-12', NULL,            'MSC MADHU B',      @locksley,   NULL,                   'arribado'),
  ('Zodo Tire Co',       'CHINA',    'Neumáticos', 'LY25040591',    29282.30,  '2025-06-25', '2025-08-30', 'QDXSE25062453', 'EVER OWN',         @transcargo, NULL,                   'arribado'),
  ('Zodo Tire Co',       'CHINA',    'Neumáticos', 'LY25051812-1',  27477.70,  '2025-07-12', '2025-09-20', 'QDXSE25070319', 'EVER OPUS',        @transcargo, NULL,                   'arribado'),
  ('Zodo Tire Co',       'CHINA',    'Neumáticos', 'LY25051812-2',  28449.82,  '2025-08-10', '2025-09-28', 'QDXSE25081612', 'EVER FAME',        @transcargo, NULL,                   'arribado'),
  ('Zodo Tire Co',       'CHINA',    'Neumáticos', 'LY25051812-3',  27875.12,  '2025-09-21', '2025-11-16', NULL,            'CMA CGM NIAGARA',  @transcargo, 'AGOSTO',               'arribado'),
  ('Rotary lift Haimen', 'CHINA',    'Máquinas',   'TR02-2025',     50847.71,  '2025-10-27', '2025-12-11', 'SY251068766',   'KOTA PUSAKA',      @locksley,   'SEPTIEMBRE',           'arribado'),
  ('SNAP ON INC',        'EEUU',     'Máquinas',   'ARV/65333143',  104816.40, '2025-08-13', '2025-08-30', 'MIASE2500592',  NULL,               @locksley,   NULL,                   'arribado'),
  ('GUANGZHOU JINGJIA',  'CHINA',    'Máquinas',   'A9AR0525006',   40233.94,  '2025-12-27', '2026-02-08', NULL,            'EVER FUTURE',      @locksley,   'Diciembre',            'arribado'),
  ('GUANGZHOU JINGJIA',  'CHINA',    'Máquinas',   'A9AR0525007',   100133.20, NULL,          NULL,         NULL,            NULL,               @locksley,   'Enero',                'pendiente'),
  ('Rotary lift Haimen', 'CHINA',    'Máquinas',   'TR03-2025',     61215.64,  '2026-03-10', '2026-04-23', NULL,            NULL,               NULL,        'Enero',                'embarcado'),
  ('Rotary lift Haimen', 'CHINA',    'Neumáticos', 'TR03 2025P',    2125.00,   NULL,          NULL,         NULL,            NULL,               NULL,        NULL,                   'arribado'),
  ('Zodo Tire Co',       'CHINA',    'Neumáticos', 'LY25120592',    27633.30,  NULL,          NULL,         NULL,            NULL,               @transcargo, 'Enero',                'pendiente'),
  ('Haweka',             'ALEMANIA', 'Máquinas',   'AB108756',      3602.10,   '2026-01-15', '2026-03-25', NULL,            NULL,               @locksley,   'Enero',                'embarcado'),
  ('SNAP ON INC',        'EEUU',     'Máquinas',   NULL,            114369.85, NULL,          NULL,         NULL,            NULL,               NULL,        'Marzo',                'pendiente'),
  ('SNAP ON INC',        'CHINA',    'Máquinas',   NULL,            41022.36,  NULL,          NULL,         NULL,            NULL,               NULL,        NULL,                   'pendiente');

-- 3. Documentos (links)
INSERT INTO importacion_documentos (importacion_id, tipo, nombre, url) VALUES
  ((SELECT id FROM importaciones WHERE numero_proforma = 'TR01-2025'),    'link', 'Documentos', 'https://drive.google.com/file/d/1wtMjggxrYz4ZC_K-bnHpmekJ3WQAv_Vv/view?usp=sharing'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'A9AR0525002'),  'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/10spEC3NGijQb216HtIkIvFOqqqqIoJyN/edit?usp=sharing&ouid=105242450119083583783&rtpof=true&sd=true'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'A9AR0525003'),  'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/15-wFiwkZKkFpdtDGWklk2JYIJQfgsTv4-nIrQyOe9DQ/edit?usp=sharing'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'A9AR0525004'),  'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1Asqongdb09LvBSKhdPEjtLfqhRrkJrpW/edit?usp=sharing&ouid=105242450119083583783&rtpof=true&sd=true'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'A9AR0525005'),  'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1Vc5jis7Bsd-84LQUl0tOWm2ZoqimVga7YoZIP-rW9CA/edit?usp=sharing'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'LY25040591'),   'link', 'Documentos', 'https://drive.google.com/file/d/1FfNhtqjYSR6KVKY3XTLV0LPUY9tMqr4B/view?usp=sharing'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'LY25051812-1'), 'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/198WzJ9P5ttT8wRjMxYbvzgacRcsqn5Kn/edit?usp=sharing&ouid=105242450119083583783&rtpof=true&sd=true'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'LY25051812-2'), 'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1Vy4GCuHxSwQHQskSD7HwbsovKn6JTJu7/edit?usp=sharing&ouid=105242450119083583783&rtpof=true&sd=true'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'LY25051812-3'), 'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1GSIsZ3zAsbJxTCbDaEWY2mFzz0cNYFdG/edit?usp=sharing&ouid=105242450119083583783&rtpof=true&sd=true'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'TR02-2025'),    'link', 'Documentos', 'https://drive.google.com/drive/folders/1Aj-Fyy3bP2d1FCizKvzW4I99cCWlg-Tt?usp=sharing'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'ARV/65333143'), 'link', 'Documentos', 'https://drive.google.com/file/d/1jH3vUSSxZe86OtuljC2nK15am4lq9St_/view?usp=sharing'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'A9AR0525006'),  'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1dzHOGrmb1JHgoMgeA3y4RYWQaxMpOvku/edit?usp=sharing&ouid=105242450119083583783&rtpof=true&sd=true'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'A9AR0525007'),  'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1PMd4lr7loWoM5qOrTYzMjd67ow0L5GTe/edit?usp=sharing&ouid=105242450119083583783&rtpof=true&sd=true'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'TR03-2025'),    'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1TNL8-dmbSWYc0oMoCTNw9-gHomCeJVF-mtTbQ3hLYaU/edit?usp=sharing'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'LY25120592'),   'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1UVKU5UYNW3VhzpRjWCz5hLHuPy9GCFwwRn1m9D2GVr4/edit?usp=sharing&ouid=105242450119083583783&rtpof=true&sd=true'),
  ((SELECT id FROM importaciones WHERE numero_proforma = 'AB108756'),     'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1f6e6rQLZ8OaKpHD3nY1HnSWRgnzUwRwi/edit?gid=731387699#gid=731387699'),
  ((SELECT id FROM importaciones WHERE proveedor = 'SNAP ON INC' AND origen = 'EEUU'  AND monto_fob = 114369.85), 'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/1mGna5T9WRIdIFlzs_FARfKgbfJOmBiy4Ad9k1y_iCHQ/edit?usp=sharing'),
  ((SELECT id FROM importaciones WHERE proveedor = 'SNAP ON INC' AND origen = 'CHINA' AND monto_fob = 41022.36),  'link', 'Documentos', 'https://docs.google.com/spreadsheets/d/15qoVFceE2HbX3SzTwYvrPpdjOarb1iABtuINCDTOp00/edit?gid=0#gid=0');
