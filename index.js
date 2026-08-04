/**
 * MAIN WORKER - Sirve todos los archivos estáticos
 */

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    let pathname = url.pathname;

    // Si es raíz o no tiene extensión, servir index.html
    if (pathname === '/' || pathname === '') {
      pathname = '/index.html';
    }

    // Intentar servir el archivo desde ASSETS
    try {
      const response = await env.ASSETS.fetch(
        new Request(new URL(pathname, request.url), request)
      );

      // Si el archivo existe, devolverlo
      if (response.status === 200) {
        return response;
      }

      // Si es 404 y es una ruta sin extensión, intentar .html
      if (response.status === 404 && !pathname.includes('.')) {
        return await env.ASSETS.fetch(
          new Request(new URL(pathname + '.html', request.url), request)
        );
      }

      // Si es 404, servir index.html (para SPA)
      if (response.status === 404) {
        return await env.ASSETS.fetch(
          new Request(new URL('/index.html', request.url), request)
        );
      }

      return response;
    } catch (e) {
      // Error, servir index.html
      try {
        return await env.ASSETS.fetch(
          new Request(new URL('/index.html', request.url), request)
        );
      } catch (err) {
        return new Response('Error 500: No se pudo cargar el sitio', {
          status: 500,
          headers: { 'Content-Type': 'text/html; charset=utf-8' }
        });
      }
    }
  }
};
