/**
 * CLOUDFLARE WORKER - Sirve sitio estático
 * Este Worker actúa como un proxy que sirve archivos estáticos
 */

export default {
  async fetch(request, env) {
    try {
      const url = new URL(request.url);
      let pathname = url.pathname;

      // Si es raíz, servir index.html
      if (pathname === '/' || pathname === '') {
        pathname = '/index.html';
      }

      // Usar ASSETS binding si está disponible
      if (env.ASSETS) {
        try {
          const response = await env.ASSETS.fetch(new Request(new URL(pathname, request.url)));
          if (response.status === 200) {
            return response;
          }
        } catch (e) {
          console.log('ASSETS error:', e);
        }
      }

      // Fallback: Servir index.html
      return new Response(`
        <!DOCTYPE html>
        <html lang="es">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Admin Panel Ganadores</title>
          <style>
            body { font-family: Arial; text-align: center; padding: 50px; background: #f0f0f0; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; }
            h1 { color: #667eea; }
            p { color: #666; }
            a { color: #667eea; text-decoration: none; font-weight: bold; }
          </style>
        </head>
        <body>
          <div class="container">
            <h1>🚀 Admin Panel Ganadores</h1>
            <p>El panel se está cargando...</p>
            <p><a href="/">Ir al inicio</a></p>
            <hr>
            <p style="font-size: 12px; color: #999;">Si ves esto, el Worker está activo pero los archivos no se están sirviendo correctamente.<br>Verifica la configuración en Cloudflare Dashboard.</p>
          </div>
        </body>
        </html>
      `, {
        status: 200,
        headers: { 'Content-Type': 'text/html; charset=utf-8' }
      });
    } catch (error) {
      return new Response('Error: ' + error.message, { status: 500 });
    }
  }
};
