# Política de cookies

## Per què no veus cap avís de cookies

Perquè no ens en cal. TuStack **no fa servir cookies d'anàlisi, de publicitat ni de tercers**.

L'article 22.2 de la Llei 34/2002 exigeix demanar permís abans d'instal·lar cookies, tret de les estrictament necessàries per prestar un servei que l'usuari ha sol·licitat. Les úniques que fem servir són d'aquest tipus, i per això no hi ha cap bàner. Preferim no instal·lar res abans que demanar-te permís per rastrejar-te.

## Cookies que fem servir

Totes són pròpies, tècniques i necessàries. Cap serveix per analitzar el teu comportament ni per publicitat.

En una visita normal només se n'instal·len dues:

| Cookie | Per a què | Durada |
|---|---|---|
| `tustack-session` | Mantenir la teva sessió mentre navegues i, si has entrat, saber que ets tu | 2 hores |
| `XSRF-TOKEN` | Protegir els formularis davant enviaments fraudulents des d'altres llocs | 2 hores |

I només si inicies sessió marcant la casella corresponent:

| Cookie | Per a què | Durada |
|---|---|---|
| `remember_web_*` | Recordar la teva sessió per no haver de tornar a entrar | Llarga durada, fins que tanques la sessió |

Si et registres i fas servir la funció de guardar empreses o cerques, la cookie de sessió és imprescindible: sense ella no podríem saber de qui són.

## El que guardem al teu navegador sense ser cookies

Dues preferències es guarden a l'emmagatzematge local del teu dispositiu (`localStorage`). No s'envien al nostre servidor i no serveixen per identificar-te:

- `theme`: si prefereixes el mode clar o fosc.
- `umami.disabled`: si has demanat no aparèixer a les estadístiques, com s'explica més avall.

## Com mesurem les visites sense cookies

Fem servir **Umami**, una eina d'anàlisi que allotgem al nostre propi servidor i que no instal·la cookies ni crea identificadors. Les dades que obtenim són agregades i anònimes: quantes pàgines s'han vist, de quin país, amb quin tipus de dispositiu i des de quina pàgina s'hi ha arribat. No permeten reconèixer-te ni seguir-te entre llocs web.

Si tot i així prefereixes no aparèixer en aquestes estadístiques, obre la consola del navegador en aquest lloc i executa:

```js
localStorage.setItem('umami.disabled', 1)
```

Umami respecta aquesta marca i deixa de comptar en aquell navegador.

## Com eliminar o bloquejar les cookies

Pots esborrar-les o bloquejar-les des de la configuració del teu navegador:

- **Chrome:** Configuració → Privadesa i seguretat → Cookies i altres dades de llocs
- **Firefox:** Paràmetres → Privadesa i seguretat → Cookies i dades del lloc
- **Safari:** Preferències → Privadesa → Gestiona les dades de llocs web
- **Edge:** Configuració → Cookies i permisos del lloc

Si bloqueges les cookies tècniques podràs continuar navegant i cercant amb normalitat, però no podràs iniciar sessió ni guardar empreses o cerques.

## Canvis

Si algun dia incorporem cookies que no siguin estrictament necessàries, actualitzarem aquesta pàgina i et demanarem permís abans d'instal·lar-les, amb la possibilitat de rebutjar-les.
