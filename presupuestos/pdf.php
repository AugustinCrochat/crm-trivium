<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre AS cliente_nombre, c.empresa, c.telefono, c.email,
           c.direccion, c.ciudad, c.provincia
    FROM presupuestos p
    LEFT JOIN clientes c ON c.id = p.cliente_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) die('Presupuesto no encontrado.');

$items = $pdo->prepare('SELECT * FROM presupuesto_items WHERE presupuesto_id = ? ORDER BY id');
$items->execute([$id]);
$items = $items->fetchAll();

$vence = date('d/m/Y', strtotime($p['fecha'] . ' +' . $p['validez_dias'] . ' days'));

$subtotal_sin_iva = 0;
$iva_total = 0;
foreach ($items as $it) {
    $base = (float)$it['cantidad'] * (float)$it['precio_unitario'];
    $subtotal_sin_iva += $base;
    $iva_total += $base * (float)($it['iva'] ?? 0) / 100;
}
$hay_iva = $iva_total > 0;

$logo_b64 = 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhLS0gQ3JlYXRlZCB3aXRoIElua3NjYXBlIChodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy8pIC0tPgoKPHN2ZwogICB3aWR0aD0iOTUuNjEyMTM3bW0iCiAgIGhlaWdodD0iNDEuMjIyNjE4bW0iCiAgIHZpZXdCb3g9IjAgMCA5NS42MTIxMzcgNDEuMjIyNjE4IgogICB2ZXJzaW9uPSIxLjEiCiAgIGlkPSJzdmcxNjA3NCIKICAgaW5rc2NhcGU6dmVyc2lvbj0iMS4yLjIgKGIwYTg0ODY1NDEsIDIwMjItMTItMDEpIgogICBzb2RpcG9kaTpkb2NuYW1lPSJsb2dvIHRyaXZpdW0gY2VudGVyLnN2ZyIKICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiCiAgIHhtbG5zOnNvZGlwb2RpPSJodHRwOi8vc29kaXBvZGkuc291cmNlZm9yZ2UubmV0L0RURC9zb2RpcG9kaS0wLmR0ZCIKICAgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIgogICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8c29kaXBvZGk6bmFtZWR2aWV3CiAgICAgaWQ9Im5hbWVkdmlldzE2MDc2IgogICAgIHBhZ2Vjb2xvcj0iI2ZmZmZmZiIKICAgICBib3JkZXJjb2xvcj0iIzAwMDAwMCIKICAgICBib3JkZXJvcGFjaXR5PSIwLjI1IgogICAgIGlua3NjYXBlOnNob3dwYWdlc2hhZG93PSIyIgogICAgIGlua3NjYXBlOnBhZ2VvcGFjaXR5PSIwLjAiCiAgICAgaW5rc2NhcGU6cGFnZWNoZWNrZXJib2FyZD0iMCIKICAgICBpbmtzY2FwZTpkZXNrY29sb3I9IiNkMWQxZDEiCiAgICAgaW5rc2NhcGU6ZG9jdW1lbnQtdW5pdHM9Im1tIgogICAgIHNob3dncmlkPSJmYWxzZSIKICAgICBpbmtzY2FwZTp6b29tPSIyLjIwMjkyNTciCiAgICAgaW5rc2NhcGU6Y3g9IjE5Ny4wMTA3MyIKICAgICBpbmtzY2FwZTpjeT0iMTM5LjU4NzA5IgogICAgIGlua3NjYXBlOndpbmRvdy13aWR0aD0iMTg1NCIKICAgICBpbmtzY2FwZTp3aW5kb3ctaGVpZ2h0PSIxMDExIgogICAgIGlua3NjYXBlOndpbmRvdy14PSI2NiIKICAgICBpbmtzY2FwZTp3aW5kb3cteT0iMzIiCiAgICAgaW5rc2NhcGU6d2luZG93LW1heGltaXplZD0iMSIKICAgICBpbmtzY2FwZTpjdXJyZW50LWxheWVyPSJsYXllcjEiIC8+CiAgPGRlZnMKICAgICBpZD0iZGVmczE2MDcxIiAvPgogIDxnCiAgICAgaW5rc2NhcGU6bGFiZWw9IkxheWVyIDEiCiAgICAgaW5rc2NhcGU6Z3JvdXBtb2RlPSJsYXllciIKICAgICBpZD0ibGF5ZXIxIgogICAgIHRyYW5zZm9ybT0idHJhbnNsYXRlKC05OS4yODMwMDUsLTEwMi4xMzkyOCkiPgogICAgPHBhdGgKICAgICAgIGlkPSJyZWN0MjQ3NiIKICAgICAgIHN0eWxlPSJmaWxsOiMxYzFjMWM7c3Ryb2tlLXdpZHRoOjAuNjE5Nzg2O3N0cm9rZS1saW5lY2FwOnJvdW5kIgogICAgICAgZD0iTSA5OS4yODMwMDUsMTAyLjEzOTI4IEggMTk0Ljg5NTE0IFYgMTQzLjM2MTkgSCA5OS4yODMwMDUgWiIgLz4KICAgIDxnCiAgICAgICBpZD0iZzI0OTQiCiAgICAgICB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzMjkuMjQxNTMsMTcxLjU2Mjc0KSIKICAgICAgIGlua3NjYXBlOmV4cG9ydC1maWxlbmFtZT0iQzpcVXNlcnNcYXVndXNcRG93bmxvYWRzXGxvZ28gZm9uZG8gZ3Jpcy5wbmciCiAgICAgICBpbmtzY2FwZTpleHBvcnQteGRwaT0iNjI2LjU2NTE5IgogICAgICAgaW5rc2NhcGU6ZXhwb3J0LXlkcGk9IjYyNi41NjUxOSI+CiAgICAgIDxnCiAgICAgICAgIHRyYW5zZm9ybT0ibWF0cml4KDAuNTQwMTcwNywwLDAsMC41NDAxNzA3LC0xMDcuMDIxMjEsMzMuNjc4NDM4KSIKICAgICAgICAgaWQ9ImcyNDkyIj4KICAgICAgICA8ZwogICAgICAgICAgIGFyaWEtbGFiZWw9IkNFTlRFUiIKICAgICAgICAgICBpZD0idGV4dDI0ODAiCiAgICAgICAgICAgc3R5bGU9ImZvbnQtc2l6ZTozMy43NzMxcHg7bGluZS1oZWlnaHQ6MS4yNTtsZXR0ZXItc3BhY2luZzowcHg7d29yZC1zcGFjaW5nOjBweDtmaWxsOiMzZTNlM2Y7c3Ryb2tlLXdpZHRoOjAuODQ0MzI4Ij4KICAgICAgICAgIDxwYXRoCiAgICAgICAgICAgICBkPSJtIC0xOTcuMDkzNTQsLTEyNC40MDE3IGMgMy4yNzU5OSwwIDcuMjk0OTksLTEuNTg3MzMgOS44NjE3NCwtMy44MTYzNiBsIC0zLjY0NzQ5LC00LjA4NjU0IGMgLTEuNTE5NzksMS41ODczMyAtMy45MTc2OCwyLjY2ODA3IC01LjcwNzY2LDIuNjY4MDcgLTIuNjAwNTMsMCAtNS4wNjU5NiwtMi4wNjAxNiAtNS4wNjU5NiwtNS4yMzQ4MyAwLC00LjI4OTE4IDQuMDE5LC04LjMwODE4IDguMzc1NzMsLTguMzA4MTggMS45OTI2MSwwIDMuNjEzNzIsMC45Nzk0MiA0LjQ1ODA1LDIuMjk2NTcgbCA0LjcyODIzLC0zLjQxMTA5IGMgLTEuODIzNzUsLTIuMzY0MTEgLTUuMDk5NzQsLTQuMTIwMzEgLTguODQ4NTUsLTQuMTIwMzEgLTcuNTk4OTUsMCAtMTQuNTU2MjEsNi40NTA2NiAtMTQuNTU2MjEsMTMuNjc4MSAwLDUuNzc1MiA0Ljc2MjAxLDEwLjMzNDU3IDEwLjQwMjEyLDEwLjMzNDU3IHoiCiAgICAgICAgICAgICBzdHlsZT0iZm9udC1zdHlsZTppdGFsaWM7Zm9udC13ZWlnaHQ6ODAwO2ZvbnQtZmFtaWx5Ok1ldHJvcG9saXM7LWlua3NjYXBlLWZvbnQtc3BlY2lmaWNhdGlvbjonTWV0cm9wb2xpcyBVbHRyYS1Cb2xkIEl0YWxpYyc7ZmlsbDojZmZmZmZmIgogICAgICAgICAgICAgaWQ9InBhdGgxNjYzOCIgLz4KICAgICAgICAgIDxwYXRoCiAgICAgICAgICAgICBkPSJtIC0xNjMuMDg0MDMsLTE0Mi44NDE4MSAxLjA4MDczLC01LjE2NzI5IGggLTE4LjI3MTI0IGwgLTQuODI5NTYsMjMuMjAyMTIgaCAxOC4yNzEyNSBsIDEuMDgwNzQsLTUuMTY3MjggaCAtMTIuNDYyMjcgbCAwLjgxMDU1LC00LjA4NjU1IGggMTEuMzQ3NzYgbCAxLjA4MDc0LC01LjE2NzI4IGggLTExLjI4MDIxIGwgMC42NzU0NiwtMy42MTM3MiB6IgogICAgICAgICAgICAgc3R5bGU9ImZvbnQtc3R5bGU6aXRhbGljO2ZvbnQtd2VpZ2h0OjgwMDtmb250LWZhbWlseTpNZXRyb3BvbGlzOy1pbmtzY2FwZS1mb250LXNwZWNpZmljYXRpb246J01ldHJvcG9saXMgVWx0cmEtQm9sZCBJdGFsaWMnO2ZpbGw6I2ZmZmZmZiIKICAgICAgICAgICAgIGlkPSJwYXRoMTY2NDAiIC8+CiAgICAgICAgICA8cGF0aAogICAgICAgICAgICAgZD0ibSAtMTYzLjE4NTM1LC0xMjQuODA2OTggaCA1Ljc3NTIgbCAyLjkwNDQ4LC0xMy43MTE4OCA3LjI5NDk5LDEzLjcxMTg4IGggNS42NzM4OCBsIDQuODI5NTYsLTIzLjIwMjEyIGggLTUuNzc1MiBsIC0yLjkwNDQ5LDEzLjcxMTg4IC03LjI2MTIyLC0xMy43MTE4OCBoIC01LjcwNzY1IHoiCiAgICAgICAgICAgICBzdHlsZT0iZm9udC1zdHlsZTppdGFsaWM7Zm9udC13ZWlnaHQ6ODAwO2ZvbnQtZmFtaWx5Ok1ldHJvcG9saXM7LWlua3NjYXBlLWZvbnQtc3BlY2lmaWNhdGlvbjonTWV0cm9wb2xpcyBVbHRyYS1Cb2xkIEl0YWxpYyc7ZmlsbDojZmZmZmZmIgogICAgICAgICAgICAgaWQ9InBhdGgxNjY0MiIgLz4KICAgICAgICAgIDxwYXRoCiAgICAgICAgICAgICBkPSJtIC0xMzEuMzAzNTYsLTEyNC44MDY5OCBoIDUuNzc1MiBsIDMuNzQ4ODIsLTE4LjAzNDgzIGggNy4wNTg1NyBsIDEuMDQ2OTcsLTUuMTY3MjkgaCAtMTkuODU4NTggbCAtMS4wNDY5Nyw1LjE2NzI5IGggNy4wMjQ4IHoiCiAgICAgICAgICAgICBzdHlsZT0iZm9udC1zdHlsZTppdGFsaWM7Zm9udC13ZWlnaHQ6ODAwO2ZvbnQtZmFtaWx5Ok1ldHJvcG9saXM7LWlua3NjYXBlLWZvbnQtc3BlY2lmaWNhdGlvbjonTWV0cm9wb2xpcyBVbHRyYS1Cb2xkIEl0YWxpYyc7ZmlsbDojZmZmZmZmIgogICAgICAgICAgICAgaWQ9InBhdGgxNjY0NCIgLz4KICAgICAgICAgIDxwYXRoCiAgICAgICAgICAgICBkPSJtIC05My40MTAxMzYsLTE0Mi44NDE4MSAxLjA4MDc0LC01LjE2NzI5IGggLTE4LjI3MTI0NCBsIC00LjgyOTU2LDIzLjIwMjEyIGggMTguMjcxMjUgbCAxLjA4MDczOSwtNS4xNjcyOCBoIC0xMi40NjIyNzkgbCAwLjgxMDU2LC00LjA4NjU1IGggMTEuMzQ3NzYxIGwgMS4wODA3NCwtNS4xNjcyOCBoIC0xMS4yODAyMjEgbCAwLjY3NTQ3LC0zLjYxMzcyIHoiCiAgICAgICAgICAgICBzdHlsZT0iZm9udC1zdHlsZTppdGFsaWM7Zm9udC13ZWlnaHQ6ODAwO2ZvbnQtZmFtaWx5Ok1ldHJvcG9saXM7LWlua3NjYXBlLWZvbnQtc3BlY2lmaWNhdGlvbjonTWV0cm9wb2xpcyBVbHRyYS1Cb2xkIEl0YWxpYyc7ZmlsbDojZmZmZmZmIgogICAgICAgICAgICAgaWQ9InBhdGgxNjY0NiIgLz4KICAgICAgICAgIDxwYXRoCiAgICAgICAgICAgICBkPSJtIC03MC43NDgzODcsLTE0MS4zNTU4IGMgMCwtMy45NTE0NSAtMy4wMDU4MDYsLTYuNjUzMyAtNy40NjM4NTYsLTYuNjUzMyBoIC0xMC41NzA5ODEgbCAtNC44Mjk1NTMsMjMuMjAyMTIgaCA1Ljc3NTIgbCAxLjUxOTc5LC03LjI2MTIxIGggMi45NzIwMzMgbCA0LjQyNDI3Niw3LjI2MTIxIGggNi40MTY4OSBsIC00Ljc5NTc4MSwtNy45MDI5IGMgMy44NTAxMzQsLTEuMzE3MTUgNi41NTE5ODIsLTQuNTkzMTQgNi41NTE5ODIsLTguNjQ1OTIgeiBtIC0xMy4zNDAzNzUsLTEuMzg0NjkgaCA0LjY5NDQ2MSBjIDEuNzIyNDI4LDAgMi43NjkzOTQsMC43MDkyMyAyLjc2OTM5NCwxLjgyMzc0IDAsMS43ODk5OCAtMi4wNjAxNTksMy41Nzk5NSAtNC4yMjE2MzgsMy41Nzk5NSBoIC00LjM5MDUwMyB6IgogICAgICAgICAgICAgc3R5bGU9ImZvbnQtc3R5bGU6aXRhbGljO2ZvbnQtd2VpZ2h0OjgwMDtmb250LWZhbWlseTpNZXRyb3BvbGlzOy1pbmtzY2FwZS1mb250LXNwZWNpZmljYXRpb246J01ldHJvcG9saXMgVWx0cmEtQm9sZCBJdGFsaWMnO2ZpbGw6I2ZmZmZmZiIKICAgICAgICAgICAgIGlkPSJwYXRoMTY2NDgiIC8+CiAgICAgICAgPC9nPgogICAgICAgIDxnCiAgICAgICAgICAgaWQ9ImcyNDkwIgogICAgICAgICAgIHRyYW5zZm9ybT0idHJhbnNsYXRlKC00LjkxMzY5MjgpIj4KICAgICAgICAgIDxnCiAgICAgICAgICAgICBhcmlhLWxhYmVsPSJSSVZJVU0iCiAgICAgICAgICAgICBpZD0idGV4dDI0ODQiCiAgICAgICAgICAgICBzdHlsZT0iZm9udC1zaXplOjMzLjc3MzFweDtsaW5lLWhlaWdodDoxLjI1O2xldHRlci1zcGFjaW5nOjBweDt3b3JkLXNwYWNpbmc6MHB4O2ZpbGw6IzNlM2UzZjtzdHJva2Utd2lkdGg6MC44NDQzMjgiPgogICAgICAgICAgICA8cGF0aAogICAgICAgICAgICAgICBkPSJtIC0xNjQuMzU3OTIsLTE3NC4zNjkwOSBjIDAsLTMuOTUxNDUgLTMuMDA1ODEsLTYuNjUzMyAtNy40NjM4NiwtNi42NTMzIGggLTEwLjU3MDk4IGwgLTQuODI5NTUsMjMuMjAyMTIgaCA1Ljc3NTIgbCAxLjUxOTc5LC03LjI2MTIxIGggMi45NzIwMyBsIDQuNDI0MjgsNy4yNjEyMSBoIDYuNDE2ODkgbCAtNC43OTU3OCwtNy45MDI5IGMgMy44NTAxMywtMS4zMTcxNSA2LjU1MTk4LC00LjU5MzE0IDYuNTUxOTgsLTguNjQ1OTIgeiBtIC0xMy4zNDAzOCwtMS4zODQ2OSBoIDQuNjk0NDYgYyAxLjcyMjQzLDAgMi43Njk0LDAuNzA5MjMgMi43Njk0LDEuODIzNzQgMCwxLjc4OTk4IC0yLjA2MDE2LDMuNTc5OTUgLTQuMjIxNjQsMy41Nzk5NSBoIC00LjM5MDUgeiIKICAgICAgICAgICAgICAgc3R5bGU9ImZvbnQtc3R5bGU6aXRhbGljO2ZvbnQtd2VpZ2h0OjgwMDtmb250LWZhbWlseTpNZXRyb3BvbGlzOy1pbmtzY2FwZS1mb250LXNwZWNpZmljYXRpb246J01ldHJvcG9saXMgVWx0cmEtQm9sZCBJdGFsaWMnO2ZpbGw6I2ZmZmZmZiIKICAgICAgICAgICAgICAgaWQ9InBhdGgxNjY1MSIgLz4KICAgICAgICAgICAgPHBhdGgKICAgICAgICAgICAgICAgZD0ibSAtMTYzLjI0MzM5LC0xNTcuODIwMjcgaCA1Ljc3NTIgbCA0LjgyOTU2LC0yMy4yMDIxMiBoIC01Ljc3NTIgeiIKICAgICAgICAgICAgICAgc3R5bGU9ImZvbnQtc3R5bGU6aXRhbGljO2ZvbnQtd2VpZ2h0OjgwMDtmb250LWZhbWlseTpNZXRyb3BvbGlzOy1pbmtzY2FwZS1mb250LXNwZWNpZmljYXRpb246J01ldHJvcG9saXMgVWx0cmEtQm9sZCBJdGFsaWMnO2ZpbGw6I2ZmZmZmZiIKICAgICAgICAgICAgICAgaWQ9InBhdGgxNjY1MyIgLz4KICAgICAgICAgICAgPHBhdGgKICAgICAgICAgICAgICAgZD0ibSAtMTMxLjkzNTY5LC0xODEuMDIyMzkgLTkuMDE3NDIsMTQuOTYxNDkgLTIuODM2OTQsLTE0Ljk2MTQ5IGggLTYuMzgzMTEgbCA0LjMyMjk1LDIzLjA2NzAzIC0wLjA2NzUsMC4xMzUwOSBoIDYuNTg1NzUgbCAxNC4wMTU4NCwtMjMuMjAyMTIgeiIKICAgICAgICAgICAgICAgc3R5bGU9ImZvbnQtc3R5bGU6aXRhbGljO2ZvbnQtd2VpZ2h0OjgwMDtmb250LWZhbWlseTpNZXRyb3BvbGlzOy1pbmtzY2FwZS1mb250LXNwZWNpZmljYXRpb246J01ldHJvcG9saXMgVWx0cmEtQm9sZCBJdGFsaWMnO2ZpbGw6I2ZmZmZmZiIKICAgICAgICAgICAgICAgaWQ9InBhdGgxNjY1NSIgLz4KICAgICAgICAgICAgPHBhdGgKICAgICAgICAgICAgICAgZD0ibSAtMTI3LjY0NjUxLC0xNTcuODIwMjcgaCA1Ljc3NTIgbCA0LjgyOTU2LC0yMy4yMDIxMiBoIC01Ljc3NTIgeiIKICAgICAgICAgICAgICAgc3R5bGU9ImZvbnQtc3R5bGU6aXRhbGljO2ZvbnQtd2VpZ2h0OjgwMDtmb250LWZhbWlseTpNZXRyb3BvbGlzOy1pbmtzY2FwZS1mb250LXNwZWNpZmljYXRpb246J01ldHJvcG9saXMgVWx0cmEtQm9sZCBJdGFsaWMnO2ZpbGw6I2ZmZmZmZiIKICAgICAgICAgICAgICAgaWQ9InBhdGgxNjY1NyIgLz4KICAgICAgICAgICAgPHBhdGgKICAgICAgICAgICAgICAgZD0ibSAtMTA2LjYwNTgzLC0xNTcuNDE0OTkgYyA2LjE4MDQ3LDAgMTEuMzEzOTg1LC00LjE1NDA5IDEyLjU5NzM2MiwtMTAuMzM0NTcgbCAyLjc2OTM5NSwtMTMuMjcyODMgaCAtNS43NzUyMDEgbCAtMi42MDA1MjgsMTIuNTI5ODIgYyAtMC42NzU0NTgsMy4yNzU5OSAtMy40NDQ4NTgsNS44NDI3NSAtNi41NTE5NzgsNS44NDI3NSAtMi43Njk0LDAgLTQuMjIxNjQsLTIuMDkzOTMgLTMuNTc5OTUsLTUuMzY5OTIgbCAyLjcwMTg1LC0xMy4wMDI2NSBoIC01Ljc0MTQzIGwgLTIuNjY4MDgsMTIuODAwMDEgYyAtMS4yODMzOCw2LjIxNDI1IDIuMzY0MTIsMTAuODA3MzkgOC44NDg1NiwxMC44MDczOSB6IgogICAgICAgICAgICAgICBzdHlsZT0iZm9udC1zdHlsZTppdGFsaWM7Zm9udC13ZWlnaHQ6ODAwO2ZvbnQtZmFtaWx5Ok1ldHJvcG9saXM7LWlua3NjYXBlLWZvbnQtc3BlY2lmaWNhdGlvbjonTWV0cm9wb2xpcyBVbHRyYS1Cb2xkIEl0YWxpYyc7ZmlsbDojZmZmZmZmIgogICAgICAgICAgICAgICBpZD0icGF0aDE2NjU5IiAvPgogICAgICAgICAgICA8cGF0aAogICAgICAgICAgICAgICBkPSJtIC05MS43Nzk0NjMsLTE1Ny44MjAyNyBoIDUuNzc1MiBsIDIuODcwNzE0LC0xMy40NDE2OSA0LjIyMTYzOCwxMi4wOTA3NyA5LjE1MjUxLC0xMi4wOTA3NyAtMi44NzA3MTMsMTMuNDQxNjkgaCA1Ljc3NTIgbCA0LjgyOTU1NCwtMjMuMjAyMTIgaCAtNS44NDI3NDcgbCAtOS4yNTM4MywxMi4wOTA3NyAtNC4xMjAzMTgsLTEyLjA5MDc3IGggLTUuNzA3NjU1IHoiCiAgICAgICAgICAgICAgIHN0eWxlPSJmb250LXN0eWxlOml0YWxpYztmb250LXdlaWdodDo4MDA7Zm9udC1mYW1pbHk6TWV0cm9wb2xpczstaW5rc2NhcGUtZm9udC1zcGVjaWZpY2F0aW9uOidNZXRyb3BvbGlzIFVsdHJhLUJvbGQgSXRhbGljJztmaWxsOiNmZmZmZmYiCiAgICAgICAgICAgICAgIGlkPSJwYXRoMTY2NjEiIC8+CiAgICAgICAgICA8L2c+CiAgICAgICAgICA8ZwogICAgICAgICAgICAgYXJpYS1sYWJlbD0iVCIKICAgICAgICAgICAgIGlkPSJ0ZXh0MjQ4OCIKICAgICAgICAgICAgIHN0eWxlPSJmb250LXNpemU6MzMuNzczMXB4O2xpbmUtaGVpZ2h0OjEuMjU7bGV0dGVyLXNwYWNpbmc6MHB4O3dvcmQtc3BhY2luZzowcHg7ZmlsbDojZmYxZTAwO3N0cm9rZS13aWR0aDowLjg0NDMyOCI+CiAgICAgICAgICAgIDxwYXRoCiAgICAgICAgICAgICAgIGQ9Im0gLTIwMy4wODgzMSwtMTU3LjgyMDI3IGggNS43NzUyIGwgMy43NDg4MSwtMTguMDM0ODMgaCA3LjA1ODU4IGwgMS4wNDY5NywtNS4xNjcyOSBoIC0xOS44NTg1OSBsIC0xLjA0Njk2LDUuMTY3MjkgaCA3LjAyNDggeiIKICAgICAgICAgICAgICAgc3R5bGU9ImZvbnQtc3R5bGU6aXRhbGljO2ZvbnQtd2VpZ2h0OjgwMDtmb250LWZhbWlseTpNZXRyb3BvbGlzOy1pbmtzY2FwZS1mb250LXNwZWNpZmljYXRpb246J01ldHJvcG9saXMgVWx0cmEtQm9sZCBJdGFsaWMnIgogICAgICAgICAgICAgICBpZD0icGF0aDE2NjY0IiAvPgogICAgICAgICAgPC9nPgogICAgICAgIDwvZz4KICAgICAgPC9nPgogICAgPC9nPgogIDwvZz4KPC9zdmc+Cg==';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Presupuesto #<?= str_pad($id,5,'0',STR_PAD_LEFT) ?> — Trivium Center</title>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1a1a1a; font-size: 14px; background: #f0f0f0; }
.page { max-width: 820px; margin: 0 auto; background: #fff; }

/* Header bar */
.header-bar {
  background: #1c1c1c;
  padding: 24px 36px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.header-bar img { height: 38px; }
.doc-info { text-align: right; }
.doc-info .doc-label { font-size: 11px; color: #888; letter-spacing: .08em; text-transform: uppercase; }
.doc-info .doc-num { font-size: 22px; font-weight: 700; color: #fff; letter-spacing: .02em; }
.doc-info .doc-sub { font-size: 12px; color: #aaa; margin-top: 2px; }

/* Red accent bar */
.accent-bar { height: 4px; background: #ff1e00; }

/* Body */
.body { padding: 32px 36px; }

.meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
.meta-block .label { font-size: 10px; text-transform: uppercase; letter-spacing: .07em; color: #999; margin-bottom: 6px; }
.meta-block .value { font-size: 14px; color: #1a1a1a; line-height: 1.5; }
.meta-block .value strong { font-size: 16px; font-weight: 700; display: block; }
.meta-block.right { text-align: right; }

/* Table */
table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
thead tr { background: #1c1c1c; }
thead th {
  color: #fff; padding: 10px 14px; font-size: 11px; font-weight: 600;
  text-transform: uppercase; letter-spacing: .06em; text-align: left;
}
thead th.r { text-align: right; }
tbody tr { border-bottom: 1px solid #f0f0f0; }
tbody tr:last-child { border-bottom: none; }
tbody td { padding: 11px 14px; font-size: 13px; vertical-align: top; color: #333; }
tbody td.r { text-align: right; white-space: nowrap; }
tbody tr:nth-child(even) { background: #fafafa; }

/* Totals */
.totals-wrap { display: flex; justify-content: flex-end; margin-top: 16px; margin-bottom: 8px; }
.totals-box { min-width: 220px; }
.totals-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; color: #555; border-bottom: 1px solid #f0f0f0; }
.totals-row:last-child { border-bottom: none; border-top: 2px solid #1c1c1c; padding-top: 10px; margin-top: 4px; }
.totals-row.total-final .lbl { font-size: 14px; font-weight: 700; color: #1a1a1a; }
.totals-row.total-final .amt { font-size: 20px; font-weight: 800; color: #ff1e00; }

/* Notes */
.notes { margin-top: 24px; border-left: 3px solid #ff1e00; padding: 10px 16px; background: #fafafa; border-radius: 0 6px 6px 0; }
.notes .label { font-size: 10px; text-transform: uppercase; letter-spacing: .07em; color: #999; margin-bottom: 5px; }
.notes p { font-size: 13px; color: #444; line-height: 1.5; white-space: pre-wrap; }

/* Footer */
.footer { margin-top: 40px; padding: 16px 36px; background: #1c1c1c; display: flex; justify-content: space-between; align-items: center; }
.footer p { font-size: 11px; color: #888; }
.footer .validity { font-size: 11px; color: #aaa; text-align: right; }

/* Botones de acción (solo pantalla) */
.actions { position: fixed; top: 16px; right: 16px; display: flex; gap: 8px; z-index: 100; }
.btn-action {
  border: none; padding: 9px 18px; border-radius: 8px; font-size: 13px;
  font-weight: 600; cursor: pointer;
}
.btn-print { background: #1c1c1c; color: #fff; }
.btn-img   { background: #ff1e00; color: #fff; }

@media print {
  body { background: #fff; }
  .actions { display: none; }
  .page { box-shadow: none; }
}
</style>
</head>
<body>

<div class="actions">
  <button class="btn-action btn-print" onclick="window.print()">🖨 Imprimir / PDF</button>
  <button class="btn-action btn-img"   onclick="descargarImagen()">↓ Imagen</button>
</div>

<div class="page" id="pagina-presupuesto">

  <div class="header-bar">
    <img src="data:image/svg+xml;base64,<?= $logo_b64 ?>" alt="Trivium Center">
    <div class="doc-info">
      <div class="doc-label">Presupuesto</div>
      <div class="doc-num">#<?= str_pad($id,5,'0',STR_PAD_LEFT) ?></div>
      <div class="doc-sub">Fecha: <?= fecha($p['fecha']) ?></div>
    </div>
  </div>
  <div class="accent-bar"></div>

  <div class="body">

    <div class="meta-grid">
      <div class="meta-block">
        <div class="label">Cliente</div>
        <div class="value">
          <strong><?= esc($p['empresa'] ?: $p['cliente_nombre'] ?: 'Sin asignar') ?></strong>
          <?php if ($p['empresa'] && $p['cliente_nombre']): ?><?= esc($p['cliente_nombre']) ?><br><?php endif; ?>
          <?php if ($p['email']): ?><?= esc($p['email']) ?><br><?php endif; ?>
          <?php if ($p['telefono']): ?><?= esc($p['telefono']) ?><br><?php endif; ?>
          <?php if ($p['ciudad']): ?><?= esc($p['ciudad']) ?><?= $p['provincia'] ? ', '.esc($p['provincia']) : '' ?><?php endif; ?>
        </div>
      </div>
      <div class="meta-block right">
        <div class="label">Validez</div>
        <div class="value">Hasta el <strong><?= $vence ?></strong></div>
        <div class="label" style="margin-top:14px">Condiciones</div>
        <div class="value">Precios en pesos argentinos</div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Descripción</th>
          <th class="r">Cant.</th>
          <th class="r">P. Unit. s/IVA</th>
          <th class="r">IVA</th>
          <th class="r">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it):
          $base    = (float)$it['cantidad'] * (float)$it['precio_unitario'];
          $iva_val = (float)($it['iva'] ?? 0);
          $sub     = $base * (1 + $iva_val / 100);
        ?>
        <tr>
          <td><?= esc($it['descripcion']) ?></td>
          <td class="r"><?= number_format((float)$it['cantidad'], 2, ',', '.') ?></td>
          <td class="r"><?= money($it['precio_unitario']) ?></td>
          <td class="r"><?= $iva_val > 0 ? $iva_val.'%' : '—' ?></td>
          <td class="r"><?= money($sub) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals-wrap">
      <div class="totals-box">
        <?php if ($hay_iva): ?>
        <div class="totals-row">
          <span class="lbl">Subtotal s/IVA</span>
          <span class="amt"><?= money($subtotal_sin_iva) ?></span>
        </div>
        <div class="totals-row">
          <span class="lbl">IVA</span>
          <span class="amt"><?= money($iva_total) ?></span>
        </div>
        <?php endif; ?>
        <div class="totals-row total-final">
          <span class="lbl">Total</span>
          <span class="amt"><?= money($p['total']) ?></span>
        </div>
      </div>
    </div>

    <?php if ($p['notas']): ?>
    <div class="notes">
      <div class="label">Observaciones</div>
      <p><?= esc($p['notas']) ?></p>
    </div>
    <?php endif; ?>

  </div>

  <div class="footer">
    <p>Trivium Center — Distribuidora de maquinaria para talleres automotrices<br>info@trivium.com.ar</p>
    <p class="validity">Válido hasta el <?= $vence ?></p>
  </div>

</div>

<script>
async function descargarImagen() {
  const el  = document.getElementById('pagina-presupuesto');
  const canvas = await html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
  const link = document.createElement('a');
  link.download = 'presupuesto-<?= str_pad($id,5,'0',STR_PAD_LEFT) ?>.png';
  link.href = canvas.toDataURL('image/png');
  link.click();
}
</script>

</body>
</html>
