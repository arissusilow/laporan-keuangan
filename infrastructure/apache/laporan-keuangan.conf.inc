# Laporan Keuangan is served by Docker on loopback. Keep these rules before
# the portal catch-all ProxyPass rule.
RewriteRule ^/laporan-keuangan$ /laporan-keuangan/ [R=302,L,NE]

ProxyPass        /laporan-keuangan/ http://127.0.0.1:8181/ nocanon retry=0 timeout=300
ProxyPassReverse /laporan-keuangan/ http://127.0.0.1:8181/

<Location /laporan-keuangan/>
    RequestHeader set X-Forwarded-Prefix "/laporan-keuangan"
    RequestHeader set X-Forwarded-Proto "https"
    RequestHeader set X-Forwarded-Port "443"
    RequestHeader set X-Forwarded-Host "secure.internal.pn-madiunkota.go.id"
    ProxyPassReverseCookiePath / /laporan-keuangan/
    Require all granted
</Location>
