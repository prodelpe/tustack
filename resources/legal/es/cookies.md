# Política de cookies

## Por qué no ves un aviso de cookies

Porque no lo necesitamos. TuStack **no usa cookies de análisis, de publicidad ni de terceros**.

El artículo 22.2 de la Ley 34/2002 exige pedir permiso antes de instalar cookies, salvo las estrictamente necesarias para prestar un servicio que el usuario ha solicitado. Las únicas que usamos son de ese tipo, y por eso no hay banner. Preferimos no instalar nada a pedirte permiso para rastrearte.

## Cookies que usamos

Todas son propias, técnicas y necesarias. Ninguna sirve para analizar tu comportamiento ni para publicidad.

En una visita normal solo se instalan dos:

| Cookie | Para qué | Duración |
|---|---|---|
| `tustack-session` | Mantener tu sesión mientras navegas y, si has entrado, saber que eres tú | 2 horas |
| `XSRF-TOKEN` | Proteger los formularios frente a envíos fraudulentos desde otros sitios | 2 horas |

Y solo si inicias sesión marcando la casilla correspondiente:

| Cookie | Para qué | Duración |
|---|---|---|
| `remember_web_*` | Recordar tu sesión para no tener que volver a entrar | Larga duración, hasta que cierres sesión |

Si te registras y usas la función de guardar empresas o búsquedas, la cookie de sesión es imprescindible: sin ella no podríamos saber a quién pertenecen.

## Lo que guardamos en tu navegador sin ser cookies

Dos preferencias se guardan en el almacenamiento local de tu dispositivo (`localStorage`). No se envían a nuestro servidor y no sirven para identificarte:

- `theme`: si prefieres el modo claro u oscuro.
- `umami.disabled`: si has pedido no aparecer en las estadísticas, como se explica más abajo.

## Cómo medimos las visitas sin cookies

Usamos **Umami**, una herramienta de análisis que alojamos en nuestro propio servidor y que no instala cookies ni crea identificadores. Los datos que obtenemos son agregados y anónimos: cuántas páginas se han visto, de qué país, con qué tipo de dispositivo y desde qué página se ha llegado. No permiten reconocerte ni seguirte entre sitios web.

Si aun así prefieres no aparecer en esas estadísticas, abre la consola de tu navegador en este sitio y ejecuta:

```js
localStorage.setItem('umami.disabled', 1)
```

Umami respeta esa marca y deja de contar en ese navegador.

## Cómo eliminar o bloquear las cookies

Puedes borrarlas o bloquearlas desde la configuración de tu navegador:

- **Chrome:** Configuración → Privacidad y seguridad → Cookies y otros datos de sitios
- **Firefox:** Ajustes → Privacidad y seguridad → Cookies y datos del sitio
- **Safari:** Preferencias → Privacidad → Gestionar datos de sitios web
- **Edge:** Configuración → Cookies y permisos del sitio

Si bloqueas las cookies técnicas podrás seguir navegando y buscando con normalidad, pero no podrás iniciar sesión ni guardar empresas o búsquedas.

## Cambios

Si algún día incorporamos cookies que no sean estrictamente necesarias, actualizaremos esta página y te pediremos permiso antes de instalarlas, con la posibilidad de rechazarlas.
