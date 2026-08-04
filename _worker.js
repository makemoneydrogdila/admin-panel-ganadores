export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    let pathname = url.pathname;

    // Si es raíz, servir index.html
    if (pathname === '/' || pathname === '') {
      pathname = '/index.html';
    }

    try {
      const response = await env.ASSETS.fetch(
        new Request(new URL(pathname, request.url), request)
      );

      if (response.status === 404) {
        // Si no encuentra el archivo, servir index.html
        return await env.ASSETS.fetch(
          new Request(new URL('/index.html', request.url), request)
        );
      }

      return response;
    } catch (e) {
      // Si hay error, servir index.html
      return await env.ASSETS.fetch(
        new Request(new URL('/index.html', request.url), request)
      );
    }
  },
};
