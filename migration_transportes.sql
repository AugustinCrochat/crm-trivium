-- =====================================================
-- Migración: Nuevo esquema de transportes + notas envíos
-- Ejecutar en la base de datos u982106244_crm_trivium
-- =====================================================

-- 0. Desactivar chequeo de claves foráneas
SET FOREIGN_KEY_CHECKS = 0;

-- 0b. Limpiar referencias en envios para evitar IDs huérfanos
UPDATE envios SET transporte_id = NULL WHERE transporte_id IS NOT NULL;

-- 0c. Eliminar restricciones de clave foránea que apuntan a transportes
-- (ajustar el nombre si es diferente en tu base)
ALTER TABLE envios DROP FOREIGN KEY IF EXISTS envios_ibfk_2;
ALTER TABLE envios DROP FOREIGN KEY IF EXISTS fk_envios_transporte;

-- 1. Eliminar tabla auxiliar de ciudades (ya no se usa)
DROP TABLE IF EXISTS transporte_ciudades;

-- 2. Recrear tabla transportes con el nuevo esquema
--    Cada fila = un transporte + una ciudad destino
DROP TABLE IF EXISTS transportes;

CREATE TABLE transportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    direccion VARCHAR(500) DEFAULT '',
    contacto VARCHAR(500) DEFAULT '',
    ciudad VARCHAR(255) DEFAULT '',
    provincia VARCHAR(255) DEFAULT '',
    notas TEXT DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insertar datos de transportes
INSERT INTO `transportes` (`nombre`, `direccion`, `contacto`, `ciudad`, `provincia`, `notas`, `activo`) VALUES

-- Expreso Rios del Sur
('Expreso Rios del Sur', 'Julio Troxler 3190', 'https://www.expresoriosdelsur.com.ar/#contacto', 'Cipolletti', 'Río Negro', '', 1),
('Expreso Rios del Sur', 'Julio Troxler 3190', 'https://www.expresoriosdelsur.com.ar/#contacto', 'Neuquén', 'Neuquén', '', 1),
('Expreso Rios del Sur', 'Julio Troxler 3190', 'https://www.expresoriosdelsur.com.ar/#contacto', 'Bariloche', 'Río Negro', '', 1),
('Expreso Rios del Sur', 'Julio Troxler 3190', 'https://www.expresoriosdelsur.com.ar/#contacto', 'San Martín de los Andes', 'Neuquén', '', 1),
('Expreso Rios del Sur', 'Julio Troxler 3190', 'https://www.expresoriosdelsur.com.ar/#contacto', 'Villa La Angostura', 'Neuquén', '', 1),

-- Transporte Libertador
('Transporte Libertador', 'HTN, Maestra Baldini 1710, B1678 Caseros', 'https://www.translibertador.com/', 'San Luis', 'San Luis', '', 1),
('Transporte Libertador', 'HTN, Maestra Baldini 1710, B1678 Caseros', 'https://www.translibertador.com/', 'Mendoza', 'Mendoza', '', 1),

-- Expreso Demonte
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Resistencia', 'Chaco', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Corrientes', 'Corrientes', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Formosa', 'Formosa', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Laguna Brava', 'Corrientes', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'San Luis del Palmar', 'Corrientes', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'San Cosme', 'Corrientes', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Riachuelo', 'Corrientes', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Santa Ana', 'Corrientes', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Paso de la Patria', 'Corrientes', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Barranqueras', 'Chaco', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Puerto Vilelas', 'Chaco', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Puerto Tirol', 'Chaco', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Colonia Benitez', 'Chaco', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Margarita Belén', 'Chaco', '', 1),
('Expreso Demonte', 'Pergamino 3751 Nave B Mod 29 a 31', 'https://www.expresodemonte.com/web/cotizacion-cuenta-0/#2', 'Fontana', 'Chaco', '', 1),

-- Expreso Jumbo
('Expreso Jumbo', 'Av. Lisandro de la Torre 3648, C1439 CABA', '', 'Córdoba', 'Córdoba', '', 1),
('Expreso Jumbo', 'Av. Lisandro de la Torre 3648, C1439 CABA', '', 'Rosario', 'Santa Fe', '', 1),

-- Central Argentino Misiones
('Central Argentino Misiones', 'Traful 3773', 'https://www.centralargentinosrl.com.ar/page4.html#form1-14', 'Posadas', 'Misiones', 'Envían a toda la provincia de Misiones', 1),
('Central Argentino Misiones', 'Traful 3773', 'https://www.centralargentinosrl.com.ar/page4.html#form1-14', 'Oberá', 'Misiones', '', 1),
('Central Argentino Misiones', 'Traful 3773', 'https://www.centralargentinosrl.com.ar/page4.html#form1-14', 'Apóstoles', 'Misiones', '', 1),
('Central Argentino Misiones', 'Traful 3773', 'https://www.centralargentinosrl.com.ar/page4.html#form1-14', 'El Soberbio', 'Misiones', '', 1),

-- Transporte Gonzalez (360°)
('Transporte Gonzalez (360°)', 'Zuviria 6571 - Villa Lugano', 'https://ofimovil.net.ar/LOG360/web/GONZALEZ/cotizar2', 'Salta', 'Salta', 'Amplia cobertura en interior', 1),
('Transporte Gonzalez (360°)', 'Zuviria 6571 - Villa Lugano', 'https://ofimovil.net.ar/LOG360/web/GONZALEZ/cotizar2', 'Jujuy', 'Jujuy', '', 1),
('Transporte Gonzalez (360°)', 'Zuviria 6571 - Villa Lugano', 'https://ofimovil.net.ar/LOG360/web/GONZALEZ/cotizar2', 'Tucumán', 'Tucumán', '', 1),
('Transporte Gonzalez (360°)', 'Zuviria 6571 - Villa Lugano', 'https://ofimovil.net.ar/LOG360/web/GONZALEZ/cotizar2', 'Córdoba', 'Córdoba', '', 1),
('Transporte Gonzalez (360°)', 'Zuviria 6571 - Villa Lugano', 'https://ofimovil.net.ar/LOG360/web/GONZALEZ/cotizar2', 'Rosario', 'Santa Fe', '', 1),
('Transporte Gonzalez (360°)', 'Zuviria 6571 - Villa Lugano', 'https://ofimovil.net.ar/LOG360/web/GONZALEZ/cotizar2', 'Santiago del Estero', 'Santiago del Estero', '', 1),
('Transporte Gonzalez (360°)', 'Zuviria 6571 - Villa Lugano', 'https://ofimovil.net.ar/LOG360/web/GONZALEZ/cotizar2', 'Catamarca', 'Catamarca', '', 1),
('Transporte Gonzalez (360°)', 'Zuviria 6571 - Villa Lugano', 'https://ofimovil.net.ar/LOG360/web/GONZALEZ/cotizar2', 'La Rioja', 'La Rioja', '', 1),

-- Transporte Almafuerte (360°)
('Transporte Almafuerte (360°)', 'Charrúa 3520 - Pompeya - CABA', 'https://www.transalmafuerte.com/#!/-pedido-de-presupuestos/', 'Rosario', 'Santa Fe', '', 1),
('Transporte Almafuerte (360°)', 'Charrúa 3520 - Pompeya - CABA', 'https://www.transalmafuerte.com/#!/-pedido-de-presupuestos/', 'Santa Fe', 'Santa Fe', '', 1),

-- Expreso El Vasquito (360°)
('Expreso El Vasquito (360°)', 'CTC - Pergamino 3751 – Módulos 38 y 39 CABA', 'https://ofimovil.net.ar/LOG360/web/VASQUITO/cotizar2', 'Tres Arroyos', 'Buenos Aires', '', 1),
('Expreso El Vasquito (360°)', 'CTC - Pergamino 3751 – Módulos 38 y 39 CABA', 'https://ofimovil.net.ar/LOG360/web/VASQUITO/cotizar2', 'Lobos', 'Buenos Aires', '', 1),
('Expreso El Vasquito (360°)', 'CTC - Pergamino 3751 – Módulos 38 y 39 CABA', 'https://ofimovil.net.ar/LOG360/web/VASQUITO/cotizar2', 'Bahía Blanca', 'Buenos Aires', '', 1),
('Expreso El Vasquito (360°)', 'CTC - Pergamino 3751 – Módulos 38 y 39 CABA', 'https://ofimovil.net.ar/LOG360/web/VASQUITO/cotizar2', 'Pedro Luro', 'Buenos Aires', '', 1),
('Expreso El Vasquito (360°)', 'CTC - Pergamino 3751 – Módulos 38 y 39 CABA', 'https://ofimovil.net.ar/LOG360/web/VASQUITO/cotizar2', 'Viedma', 'Río Negro', '', 1),

-- Expreso Nuevo Valle (360°)
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'Neuquén', 'Neuquén', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'Añelo', 'Neuquén', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'Cutral Có', 'Neuquén', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'Junín de los Andes', 'Neuquén', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'Plottier', 'Neuquén', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'San Martín de los Andes', 'Neuquén', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'Cipolletti', 'Río Negro', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'General Roca', 'Río Negro', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'Bariloche', 'Río Negro', '', 1),
('Expreso Nuevo Valle (360°)', 'C. T. C. - Pergamino Nº 3751 - MODULO 41 - NAVE B', 'https://www.expresonuevovalle.com.ar/presupuestos', 'El Bolsón', 'Río Negro', '', 1),

-- Transporte Petrel (360°)
('Transporte Petrel (360°)', 'Avda. F. Fernández de la Cruz 2476', 'https://ofimovil.net.ar/LOG360/web/PETREL/cotizar2', 'Río Grande', 'Tierra del Fuego', '', 1),
('Transporte Petrel (360°)', 'Avda. F. Fernández de la Cruz 2476', 'https://ofimovil.net.ar/LOG360/web/PETREL/cotizar2', 'Ushuaia', 'Tierra del Fuego', '', 1),
('Transporte Petrel (360°)', 'Avda. F. Fernández de la Cruz 2476', 'https://ofimovil.net.ar/LOG360/web/PETREL/cotizar2', 'Río Gallegos', 'Santa Cruz', '', 1),
('Transporte Petrel (360°)', 'Avda. F. Fernández de la Cruz 2476', 'https://ofimovil.net.ar/LOG360/web/PETREL/cotizar2', 'Tolhuin', 'Tierra del Fuego', '', 1),
('Transporte Petrel (360°)', 'Avda. F. Fernández de la Cruz 2476', 'https://ofimovil.net.ar/LOG360/web/PETREL/cotizar2', 'Bahía Blanca', 'Buenos Aires', '', 1),
('Transporte Petrel (360°)', 'Avda. F. Fernández de la Cruz 2476', 'https://ofimovil.net.ar/LOG360/web/PETREL/cotizar2', 'Córdoba', 'Córdoba', '', 1),
('Transporte Petrel (360°)', 'Avda. F. Fernández de la Cruz 2476', 'https://ofimovil.net.ar/LOG360/web/PETREL/cotizar2', 'Rosario', 'Santa Fe', '', 1),

-- Logistica Pampeana (360°)
('Logistica Pampeana (360°)', 'Australia 2758 (Barracas) CABA', 'https://logisticapampeana.com/', 'Santa Rosa', 'La Pampa', 'Incluye Expreso Rocinante, Trenque Lauquen Expreso y Transporte Pico', 1),
('Logistica Pampeana (360°)', 'Australia 2758 (Barracas) CABA', 'https://logisticapampeana.com/', 'General Pico', 'La Pampa', '', 1),
('Logistica Pampeana (360°)', 'Australia 2758 (Barracas) CABA', 'https://logisticapampeana.com/', 'Huinca Renancó', 'Córdoba', '', 1),

-- Transporte Raosa (360°)
('Transporte Raosa (360°)', 'CTC - Pergamino 3751 – Villa Soldati. Nave B Módulo 56', 'https://www.raosa.com.ar/', 'Puerto Iguazú', 'Misiones', 'Cubre toda la Región del NEA', 1),
('Transporte Raosa (360°)', 'CTC - Pergamino 3751 – Villa Soldati. Nave B Módulo 56', 'https://www.raosa.com.ar/', 'Las Lomitas', 'Formosa', '', 1),
('Transporte Raosa (360°)', 'CTC - Pergamino 3751 – Villa Soldati. Nave B Módulo 56', 'https://www.raosa.com.ar/', 'Charatas', 'Chaco', '', 1),
('Transporte Raosa (360°)', 'CTC - Pergamino 3751 – Villa Soldati. Nave B Módulo 56', 'https://www.raosa.com.ar/', 'Monte Caseros', 'Corrientes', '', 1),
('Transporte Raosa (360°)', 'CTC - Pergamino 3751 – Villa Soldati. Nave B Módulo 56', 'https://www.raosa.com.ar/', 'Goya', 'Corrientes', '', 1),
('Transporte Raosa (360°)', 'CTC - Pergamino 3751 – Villa Soldati. Nave B Módulo 56', 'https://www.raosa.com.ar/', 'Gral. Belgrano', 'Formosa', '', 1),

-- Transporte Ascencio, Camionera Mendocina, Tradelog, etc. (Cuyo)
('Transporte Ascencio (360°)', 'Berón de Astrada 2796 - CABA', 'https://www.ascencio.com.ar/', 'Mendoza', 'Mendoza', '', 1),
('Transporte Ascencio (360°)', 'Berón de Astrada 2796 - CABA', 'https://www.ascencio.com.ar/', 'San Juan', 'San Juan', '', 1),
('Transporte Ascencio (360°)', 'Berón de Astrada 2796 - CABA', 'https://www.ascencio.com.ar/', 'San Luis', 'San Luis', '', 1),
('Transporte Ascencio (360°)', 'Berón de Astrada 2796 - CABA', 'https://www.ascencio.com.ar/', 'Córdoba', 'Córdoba', '', 1),

('Camionera Mendocina', 'Avda. San Pedrito 3771 Villa Soldati - CABA', 'https://ecamm.net/#presupuesto', 'Mendoza', 'Mendoza', '', 1),
('Camionera Mendocina', 'Avda. San Pedrito 3771 Villa Soldati - CABA', 'https://ecamm.net/#presupuesto', 'San Juan', 'San Juan', '', 1),
('Camionera Mendocina', 'Avda. San Pedrito 3771 Villa Soldati - CABA', 'https://ecamm.net/#presupuesto', 'San Luis', 'San Luis', '', 1),
('Camionera Mendocina', 'Avda. San Pedrito 3771 Villa Soldati - CABA', 'https://ecamm.net/#presupuesto', 'Córdoba', 'Córdoba', '', 1),
('Camionera Mendocina', 'Avda. San Pedrito 3771 Villa Soldati - CABA', 'https://ecamm.net/#presupuesto', 'Rosario', 'Santa Fe', '', 1),

('Transporte Tradelog SAU', 'CTC - Pergamino 3751, CABA. Módulos 71 al 75', 'https://www.tradelog.com.ar/', 'Mendoza', 'Mendoza', '', 1),
('Transporte Tradelog SAU', 'CTC - Pergamino 3751, CABA. Módulos 71 al 75', 'https://www.tradelog.com.ar/', 'Luján de Cuyo', 'Mendoza', '', 1),
('Transporte Tradelog SAU', 'CTC - Pergamino 3751, CABA. Módulos 71 al 75', 'https://www.tradelog.com.ar/', 'Santiago del Estero', 'Santiago del Estero', '', 1),
('Transporte Tradelog SAU', 'CTC - Pergamino 3751, CABA. Módulos 71 al 75', 'https://www.tradelog.com.ar/', 'Tucumán', 'Tucumán', '', 1),
('Transporte Tradelog SAU', 'CTC - Pergamino 3751, CABA. Módulos 71 al 75', 'https://www.tradelog.com.ar/', 'Jujuy', 'Jujuy', '', 1),
('Transporte Tradelog SAU', 'CTC - Pergamino 3751, CABA. Módulos 71 al 75', 'https://www.tradelog.com.ar/', 'Salta', 'Salta', '', 1),
('Transporte Tradelog SAU', 'CTC - Pergamino 3751, CABA. Módulos 71 al 75', 'https://www.tradelog.com.ar/', 'Bahía Blanca', 'Buenos Aires', '', 1),

-- Expreso Ara Verá (Corrientes)
('Expreso Ara Verá', 'San Pedrito 3590 - Soldati - CABA', 'https://www.expresoaravera.com/home/index.php', 'Corrientes', 'Corrientes', '', 1),
('Expreso Ara Verá', 'San Pedrito 3590 - Soldati - CABA', 'https://www.expresoaravera.com/home/index.php', 'Goya', 'Corrientes', '', 1),
('Expreso Ara Verá', 'San Pedrito 3590 - Soldati - CABA', 'https://www.expresoaravera.com/home/index.php', 'Esquina', 'Corrientes', '', 1),
('Expreso Ara Verá', 'San Pedrito 3590 - Soldati - CABA', 'https://www.expresoaravera.com/home/index.php', 'Santa Lucía', 'Corrientes', '', 1),
('Expreso Ara Verá', 'San Pedrito 3590 - Soldati - CABA', 'https://www.expresoaravera.com/home/index.php', 'Bella Vista', 'Corrientes', '', 1),
('Expreso Ara Verá', 'San Pedrito 3590 - Soldati - CABA', 'https://www.expresoaravera.com/home/index.php', 'Saladas', 'Corrientes', '', 1),
('Expreso Ara Verá', 'San Pedrito 3590 - Soldati - CABA', 'https://www.expresoaravera.com/home/index.php', 'San Miguel', 'Corrientes', '', 1),
('Expreso Ara Verá', 'San Pedrito 3590 - Soldati - CABA', 'https://www.expresoaravera.com/home/index.php', 'San Roque', 'Corrientes', '', 1),

-- La Sevillanita
('La Sevillanita', 'Pergamino 3751 (Villa Soldati) CABA (CTC)', 'https://www.lasevillanita-online.com/', 'Córdoba', 'Córdoba', '', 1),
('La Sevillanita', 'Pergamino 3751 (Villa Soldati) CABA (CTC)', 'https://www.lasevillanita-online.com/', 'La Rioja', 'La Rioja', '', 1),
('La Sevillanita', 'Pergamino 3751 (Villa Soldati) CABA (CTC)', 'https://www.lasevillanita-online.com/', 'Catamarca', 'Catamarca', '', 1),
('La Sevillanita', 'Pergamino 3751 (Villa Soldati) CABA (CTC)', 'https://www.lasevillanita-online.com/', 'Santiago del Estero', 'Santiago del Estero', '', 1),
('La Sevillanita', 'Pergamino 3751 (Villa Soldati) CABA (CTC)', 'https://www.lasevillanita-online.com/', 'Tucumán', 'Tucumán', '', 1),
('La Sevillanita', 'Pergamino 3751 (Villa Soldati) CABA (CTC)', 'https://www.lasevillanita-online.com/', 'Salta', 'Salta', '', 1),
('La Sevillanita', 'Pergamino 3751 (Villa Soldati) CABA (CTC)', 'https://www.lasevillanita-online.com/', 'Jujuy', 'Jujuy', '', 1),

-- Transporte Becerra
('Transporte Becerra', 'Fructuoso Rivera 2660, V. Soldati', 'https://www.transportebecerra.com.ar/', 'General Roca', 'Río Negro', '', 1),
('Transporte Becerra', 'Fructuoso Rivera 2660, V. Soldati', 'https://www.transportebecerra.com.ar/', 'Allen', 'Río Negro', '', 1),
('Transporte Becerra', 'Fructuoso Rivera 2660, V. Soldati', 'https://www.transportebecerra.com.ar/', 'Cipolletti', 'Río Negro', '', 1),
('Transporte Becerra', 'Fructuoso Rivera 2660, V. Soldati', 'https://www.transportebecerra.com.ar/', 'Cinco Saltos', 'Río Negro', '', 1),
('Transporte Becerra', 'Fructuoso Rivera 2660, V. Soldati', 'https://www.transportebecerra.com.ar/', 'Neuquén', 'Neuquén', '', 1),
('Transporte Becerra', 'Fructuoso Rivera 2660, V. Soldati', 'https://www.transportebecerra.com.ar/', 'Plottier', 'Neuquén', '', 1),
('Transporte Becerra', 'Fructuoso Rivera 2660, V. Soldati', 'https://www.transportebecerra.com.ar/', 'Añelo', 'Neuquén', '', 1),

-- Expreso Heral
('Expreso Heral', 'Pergamino nº 3751 – Módulo B – 40 (CTC)', 'https://expresoheral.com.ar/deposito/', 'Corrientes', 'Corrientes', '', 1),
('Expreso Heral', 'Pergamino nº 3751 – Módulo B – 40 (CTC)', 'https://expresoheral.com.ar/deposito/', 'Sauce', 'Corrientes', '', 1),
('Expreso Heral', 'Pergamino nº 3751 – Módulo B – 40 (CTC)', 'https://expresoheral.com.ar/deposito/', 'Mercedes', 'Corrientes', '', 1),
('Expreso Heral', 'Pergamino nº 3751 – Módulo B – 40 (CTC)', 'https://expresoheral.com.ar/deposito/', 'Paso de los Libres', 'Corrientes', '', 1),
('Expreso Heral', 'Pergamino nº 3751 – Módulo B – 40 (CTC)', 'https://expresoheral.com.ar/deposito/', 'Gobernador Virasoro', 'Corrientes', '', 1),

-- Expreso Lobruno
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'La Banda', 'Santiago del Estero', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Añatuya', 'Santiago del Estero', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Termas de Río Hondo', 'Santiago del Estero', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Tucumán', 'Tucumán', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Salta', 'Salta', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Orán', 'Salta', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Tartagal', 'Salta', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Catamarca', 'Catamarca', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'La Rioja', 'La Rioja', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Rafaela', 'Santa Fe', '', 1),
('Expreso Lobruno', 'Murguiondo 3317, C1439 CABA', 'https://expresolobruno.com.ar/presupuesto', 'Rosario', 'Santa Fe', '', 1),

-- Expreso Trole
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'Caseros', 'Buenos Aires', 'Gran cobertura en GBA', 1),
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'La Plata', 'Buenos Aires', '', 1),
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'Quilmes', 'Buenos Aires', '', 1),
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'Lomas de Zamora', 'Buenos Aires', '', 1),
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'Moreno', 'Buenos Aires', '', 1),
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'Merlo', 'Buenos Aires', '', 1),
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'Tigre', 'Buenos Aires', '', 1),
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'Escobar', 'Buenos Aires', '', 1),
('Expreso Trole', 'Trole 267, C1437DKE CABA', 'https://expresotrole.com.ar/wordpress/home/contacto/', 'Pilar', 'Buenos Aires', '', 1),

-- TRANSPORTADORA EL BOLSON
('TRANSPORTADORA EL BOLSON', 'Lisandro de La Torre 3648', 'https://cotizador.canguro.com.ar/transportadoraelbolson', 'El Bolsón', 'Río Negro', '', 1),
('TRANSPORTADORA EL BOLSON', 'Lisandro de La Torre 3648', 'https://cotizador.canguro.com.ar/transportadoraelbolson', 'Lago Puelo', 'Chubut', '', 1),
('TRANSPORTADORA EL BOLSON', 'Lisandro de La Torre 3648', 'https://cotizador.canguro.com.ar/transportadoraelbolson', 'El Hoyo', 'Chubut', '', 1),

-- Transportes Union
('Transportes Union', 'Lisandro de la Torre 3945 - CABA', 'https://www.transportesunion.com.ar/cotizador-online/', 'Comodoro Rivadavia', 'Chubut', '', 1),
('Transportes Union', 'Lisandro de la Torre 3945 - CABA', 'https://www.transportesunion.com.ar/cotizador-online/', 'Trelew', 'Chubut', '', 1),
('Transportes Union', 'Lisandro de la Torre 3945 - CABA', 'https://www.transportesunion.com.ar/cotizador-online/', 'Puerto Madryn', 'Chubut', '', 1),
('Transportes Union', 'Lisandro de la Torre 3945 - CABA', 'https://www.transportesunion.com.ar/cotizador-online/', 'Caleta Olivia', 'Santa Cruz', '', 1),
('Transportes Union', 'Lisandro de la Torre 3945 - CABA', 'https://www.transportesunion.com.ar/cotizador-online/', 'Neuquén', 'Neuquén', '', 1),

-- Expreso Transcia
('Expreso Transcia', 'Pepirí 1.335, C1437', 'https://www.instagram.com/expreso_transcia/', 'Viedma', 'Río Negro', '', 1),
('Expreso Transcia', 'Pepirí 1.335, C1437', 'https://www.instagram.com/expreso_transcia/', 'Bahía Blanca', 'Buenos Aires', '', 1),
('Expreso Transcia', 'Pepirí 1.335, C1437', 'https://www.instagram.com/expreso_transcia/', 'San Antonio Oeste', 'Río Negro', '', 1),

-- Transportes Ezquerra
('Transportes Ezquerra', 'Fructuoso Rivera 2650 - CABA', 'https://transportesezquerra.com.ar/index.php/encomiendas/', 'El Calafate', 'Santa Cruz', '', 1),
('Transportes Ezquerra', 'Fructuoso Rivera 2650 - CABA', 'https://transportesezquerra.com.ar/index.php/encomiendas/', 'El Chaltén', 'Santa Cruz', '', 1),

-- Trans Roca
('Trans Roca', 'John W. Cooke 3155', 'https://www.instagram.com/transroca.rn/reels/', 'Neuquén', 'Neuquén', '', 1),
('Trans Roca', 'John W. Cooke 3155', 'https://www.instagram.com/transroca.rn/reels/', 'General Roca', 'Río Negro', '', 1),
('Trans Roca', 'John W. Cooke 3155', 'https://www.instagram.com/transroca.rn/reels/', 'Cipolletti', 'Río Negro', '', 1),
('Trans Roca', 'John W. Cooke 3155', 'https://www.instagram.com/transroca.rn/reels/', 'Allen', 'Río Negro', '', 1),
('Trans Roca', 'John W. Cooke 3155', 'https://www.instagram.com/transroca.rn/reels/', 'Cinco Saltos', 'Río Negro', '', 1),

-- Transporte Pedrito
('Transporte Pedrito', 'Pergamino 3751', 'https://www.transportepedrito.com.ar/presupuesto/', 'Reconquista', 'Santa Fe', '', 1),
('Transporte Pedrito', 'Pergamino 3751', 'https://www.transportepedrito.com.ar/presupuesto/', 'Rosario', 'Santa Fe', '', 1),
('Transporte Pedrito', 'Pergamino 3751', 'https://www.transportepedrito.com.ar/presupuesto/', 'Santa Fe', 'Santa Fe', '', 1),

-- Transporte Pico / Rocinante / TAS
('Transporte Pico', 'Australia 2758', 'https://www.transportepico.com/consultas/index.php', 'General Pico', 'La Pampa', '', 1),
('Transporte Pico', 'Australia 2758', 'https://www.transportepico.com/consultas/index.php', 'Santa Rosa', 'La Pampa', '', 1),
('Expreso Rocinante', 'Australia 2758', 'https://www.expresorocinante.com/cotizador/index.php', 'General Pico', 'La Pampa', '', 1),
('Expreso Rocinante', 'Australia 2758', 'https://www.expresorocinante.com/cotizador/index.php', 'Santa Rosa', 'La Pampa', '', 1),
('Expreso Rocinante', 'Australia 2758', 'https://www.expresorocinante.com/cotizador/index.php', 'Trenque Lauquen', 'Buenos Aires', '', 1),
('Expreso TAS', 'Av. Int. Francisco Rabanal 3159', 'https://www.instagram.com/expresotas/?hl=en', 'Bahía Blanca', 'Buenos Aires', '', 1),
('Expreso TAS', 'Av. Int. Francisco Rabanal 3159', 'https://www.instagram.com/expresotas/?hl=en', 'Mar del Plata', 'Buenos Aires', '', 1),
('Expreso TAS', 'Av. Int. Francisco Rabanal 3159', 'https://www.instagram.com/expresotas/?hl=en', 'Tandil', 'Buenos Aires', '', 1),
('Expreso TAS', 'Av. Int. Francisco Rabanal 3159', 'https://www.instagram.com/expresotas/?hl=en', 'Necochea', 'Buenos Aires', '', 1),

-- Varios
('Transporte Conte', 'Paracas 261 - CABA', 'http://www.transportesconte.com/index.html', 'Pergamino', 'Buenos Aires', '', 1),
('Transporte Conte', 'Paracas 261 - CABA', 'http://www.transportesconte.com/index.html', 'Rosario', 'Santa Fe', '', 1),
('Transporte Conte', 'Paracas 261 - CABA', 'http://www.transportesconte.com/index.html', 'Córdoba', 'Córdoba', '', 1),
('Transporte Conte', 'Paracas 261 - CABA', 'http://www.transportesconte.com/index.html', 'San Luis', 'San Luis', '', 1),

('Expreso Tim-Car', 'Av. Fernandez de la Cruz 2340', 'https://expresotimcar.com.ar/sucursales/', 'Concordia', 'Entre Ríos', '', 1),
('Expreso Tim-Car', 'Av. Fernandez de la Cruz 2340', 'https://expresotimcar.com.ar/sucursales/', 'Paraná', 'Entre Ríos', '', 1),
('Expreso Tim-Car', 'Av. Fernandez de la Cruz 2340', 'https://expresotimcar.com.ar/sucursales/', 'Gualeguaychú', 'Entre Ríos', '', 1),

('Expreso Fueguino', 'Australia 2838 - Barracas', 'https://expresofueguino.com/#cotizar-envio', 'Río Grande', 'Tierra del Fuego', '', 1),
('Expreso Fueguino', 'Australia 2838 - Barracas', 'https://expresofueguino.com/#cotizar-envio', 'Ushuaia', 'Tierra del Fuego', '', 1),
('Expreso Fueguino', 'Australia 2838 - Barracas', 'https://expresofueguino.com/#cotizar-envio', 'Río Gallegos', 'Santa Cruz', '', 1),

('Expreso Lujan de Cuyo', 'Pergamino 3751, Villa Soldati (CTC) Nave D: Depósitos 86 al 88', 'https://expresolujan.com/', 'Mar del Plata', 'Buenos Aires', '', 1),
('Expreso Lujan de Cuyo', 'Pergamino 3751, Villa Soldati (CTC) Nave D: Depósitos 86 al 88', 'https://expresolujan.com/', 'San Juan', 'San Juan', '', 1),
('Expreso Lujan de Cuyo', 'Pergamino 3751, Villa Soldati (CTC) Nave D: Depósitos 86 al 88', 'https://expresolujan.com/', 'San Rafael', 'Mendoza', '', 1),

('EXPRESO BILETTA', 'Amancio Alcorta 3208 - CABA', 'https://estacionriodejaneiro.com/casa-central/', 'Río Cuarto', 'Córdoba', '', 1),
('EXPRESO BILETTA', 'Amancio Alcorta 3208 - CABA', 'https://estacionriodejaneiro.com/casa-central/', 'Villa María', 'Córdoba', '', 1),

('Empresa Alegre Transporte', 'JULIO TROXLER 3190', 'https://www.mudanzasalegre.com.ar/', 'Necochea', 'Buenos Aires', '', 1),
('Empresa Alegre Transporte', 'JULIO TROXLER 3190', 'https://www.mudanzasalegre.com.ar/', 'La Plata', 'Buenos Aires', '', 1),

('Transporte Super 73', 'FERRE 2630 - CABA', '', '9 de Julio', 'Buenos Aires', '', 1),

('Expreso BRIO', 'Av. San Pedrito 3731, C1437 CABA', 'https://brio.com.ar/index.php/presupuesto/', 'Junín', 'Buenos Aires', '', 1),
('Expreso BRIO', 'Av. San Pedrito 3731, C1437 CABA', 'https://brio.com.ar/index.php/presupuesto/', 'Mar del Plata', 'Buenos Aires', '', 1),
('Expreso BRIO', 'Av. San Pedrito 3731, C1437 CABA', 'https://brio.com.ar/index.php/presupuesto/', 'Mendoza', 'Mendoza', '', 1),
('Expreso BRIO', 'Av. San Pedrito 3731, C1437 CABA', 'https://brio.com.ar/index.php/presupuesto/', 'Tucumán', 'Tucumán', '', 1),

('Transporte Vesprini y Transcont', 'Spegazzini Dirección: Colectora Ezeiza Cañuelas KM 46500.', 'https://www.transportevesprini.com.ar/#page-top', 'Comodoro Rivadavia', 'Chubut', 'Amplia cobertura Patagonia', 1),
('Transporte Vesprini y Transcont', 'Spegazzini Dirección: Colectora Ezeiza Cañuelas KM 46500.', 'https://www.transportevesprini.com.ar/#page-top', 'Bahía Blanca', 'Buenos Aires', '', 1),
('Transporte Vesprini y Transcont', 'Spegazzini Dirección: Colectora Ezeiza Cañuelas KM 46500.', 'https://www.transportevesprini.com.ar/#page-top', 'Bariloche', 'Río Negro', '', 1),
('Transporte Vesprini y Transcont', 'Spegazzini Dirección: Colectora Ezeiza Cañuelas KM 46500.', 'https://www.transportevesprini.com.ar/#page-top', 'Mendoza', 'Mendoza', '', 1),

('Transporte Vicente', 'Crespo 3135, CABA', 'https://www.instagram.com/transportevicente/', 'Mar del Plata', 'Buenos Aires', '', 1),

('Transportes Ñandubay S.A.', 'Villa Soldati - Predio CTC Avda. Coronel Roca 3450 Nave C Módulos 64 y 65', 'https://transportesnandubay.com/presupuestos/', 'San Francisco', 'Córdoba', '', 1),
('Transportes Ñandubay S.A.', 'Villa Soldati - Predio CTC Avda. Coronel Roca 3450 Nave C Módulos 64 y 65', 'https://transportesnandubay.com/presupuestos/', 'Paraná', 'Entre Ríos', '', 1),

('Mostto Logistica y Transporte', 'Pergamino 3751 CTC - Módulo 90, Nave D, C1437 CABA', 'https://www.transportemostto.com.ar/', 'Gualeguaychú', 'Entre Ríos', '', 1),
('Mostto Logistica y Transporte', 'Pergamino 3751 CTC - Módulo 90, Nave D, C1437 CABA', 'https://www.transportemostto.com.ar/', 'Concordia', 'Entre Ríos', '', 1),
('Mostto Logistica y Transporte', 'Pergamino 3751 CTC - Módulo 90, Nave D, C1437 CABA', 'https://www.transportemostto.com.ar/', 'Paraná', 'Entre Ríos', '', 1),

('Transporte Merlo', 'Ferre 1537 - Pompeya', 'https://transportemerlo.com.ar/', 'Merlo', 'San Luis', '', 1),

('Transporte Rodriguez (Galt Logistica)', 'Portela 3479', 'https://galtlogistica.com.ar/', 'Tres Arroyos', 'Buenos Aires', '', 1),

('LOGÍSTICA ANTÁRTICA', 'Avenida Don Pedro de Mendoza 2661, La Boca, CABA', 'https://www.logisticaantartica.com/', 'Neuquén', 'Neuquén', '', 1),
('LOGÍSTICA ANTÁRTICA', 'Avenida Don Pedro de Mendoza 2661, La Boca, CABA', 'https://www.logisticaantartica.com/', 'Río Grande', 'Tierra del Fuego', '', 1),
('LOGÍSTICA ANTÁRTICA', 'Avenida Don Pedro de Mendoza 2661, La Boca, CABA', 'https://www.logisticaantartica.com/', 'Ushuaia', 'Tierra del Fuego', '', 1),

-- Andreani (nacional)
('Andreani', 'Pergamino 3751 Sector D 92 y 93, C1437 CABA', 'https://www.andreani.com/?tab=cotizar-envio', 'Buenos Aires', 'Buenos Aires', 'Cobertura nacional', 1);


-- 4. Crear tabla para notas/todo del módulo de envíos
CREATE TABLE IF NOT EXISTS envios_notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto VARCHAR(500) NOT NULL,
    completado TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Reactivar chequeo de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;
