-- INSERT para agregar usuario Samuel
-- Nombre: Samuel
-- Correo: samuel.calidad@brazostrailers.com
-- Contraseña: 1234 (hasheada con MD5)
-- Rol: calidad

INSERT INTO `usuarios` (`nombre`, `correo`, `contrasena`, `rol`) 
VALUES ('Samuel', 'samuel.calidad@brazostrailers.com', MD5('1234'), 'calidad');

-- Verificar que el usuario se insertó correctamente
SELECT * FROM usuarios WHERE correo = 'samuel.calidad@brazostrailers.com';
