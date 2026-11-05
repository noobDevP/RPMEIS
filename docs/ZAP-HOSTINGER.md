# Verify the November 5 ZAP fixes on Hostinger

The four medium CSP alerts and the low large-redirect alert all came from the same HTTP `GET /` response. It had a 795-byte Hostinger redirect page and a CSP containing only `upgrade-insecure-requests`. The other low alert came from HTTP `GET /robots.txt`: Hostinger returned a 58-byte file that differs from `public/robots.txt` and lacked `X-Content-Type-Options`.

Deploy the updated root and public `.htaccess` files and `public/index.php`. When the request reaches the origin over HTTP, PHP returns a bodyless 301 with a restrictive CSP and `nosniff`, before the application runs. The destination is the deployment hostname in `public/index.php`; update it if the production hostname changes. HTTPS should serve the repository's `public/robots.txt` with `nosniff` from `public/.htaccess`.

If Hostinger still answers either HTTP request before it reaches the origin, have Hostinger configure its edge to return the same empty 301 and headers, or hand both HTTP paths through to the verified origin redirect. In particular, the HTTP robots override needs to stop intercepting the request. Keep HTTPS enforcement active during the change. If TLS terminates at a proxy, confirm the origin correctly sees HTTPS requests as secure before routing traffic through it.

Verify with GET requests without following redirects:

```sh
curl -sS -D - -o /dev/null -w 'body bytes: %{size_download}\n' http://lawngreen-lyrebird-493319.hostingersite.com/
curl -sS -D - -o /dev/null -w 'body bytes: %{size_download}\n' http://lawngreen-lyrebird-493319.hostingersite.com/robots.txt
curl -sS -i https://lawngreen-lyrebird-493319.hostingersite.com/robots.txt
```

Both HTTP requests should return a 301 to the equivalent HTTPS URL with zero body bytes, a CSP with explicit script, style, framing and form rules, and `X-Content-Type-Options: nosniff`. The HTTPS robots request should return the repository file with `nosniff`. Then rerun ZAP against the same HTTP and HTTPS URLs.
