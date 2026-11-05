const fs = require('fs');
const path = require('path');

module.exports.config = {
  api: {
    bodyParser: false,
    externalResolver: true,
  },
};

module.exports = async function handler(req, res) {
  try {
    const configPath = path.join(__dirname, 'tunnel_config.json');
    let tunnelBase = '';
    if (fs.existsSync(configPath)) {
      const cfg = JSON.parse(fs.readFileSync(configPath, 'utf8'));
      tunnelBase = (cfg.tunnelUrl || '').replace(/\/$/, '');
    }

    if (!tunnelBase) {
      res.status(503).send('OJS Backend URL not configured.');
      return;
    }

    const vercelHost = req.headers['x-forwarded-host'] || req.headers.host || 'odol-journal.vercel.app';

    // Reconstruct original path and query parameters
    const parsedUrl = new URL(req.url || '/', `https://${vercelHost}`);
    let targetPath = parsedUrl.searchParams.get('__p') || parsedUrl.pathname;
    parsedUrl.searchParams.delete('__p');
    parsedUrl.searchParams.delete('__vp');
    parsedUrl.searchParams.delete('path');

    // Restore OJS $$$call$$$ (use function replacer so $$ is not treated as escape)
    targetPath = targetPath.replace(/\${1,3}call\${1,3}/g, () => '$$$call$$$');

    if (targetPath === '/' || targetPath === '/ODOL' || targetPath === '/ODOL/' || targetPath === '/api/proxy') {
      targetPath = '/ODOL/index.php/journal';
    } else if (!targetPath.startsWith('/ODOL/')) {
      targetPath = '/ODOL' + (targetPath.startsWith('/') ? '' : '/') + targetPath;
    }

    const qs = parsedUrl.searchParams.toString();
    const targetUrl = `${tunnelBase}${targetPath}${qs ? '?' + qs : ''}`;

    // Read raw request body for POST/PUT/PATCH/DELETE
    const chunks = [];
    if (req.method !== 'GET' && req.method !== 'HEAD') {
      for await (const chunk of req) {
        chunks.push(typeof chunk === 'string' ? Buffer.from(chunk) : chunk);
      }
    }
    const rawBody = chunks.length > 0 ? Buffer.concat(chunks) : undefined;

    // Forward all relevant request headers
    const skipHeaders = new Set([
      'host',
      'connection',
      'content-length',
      'transfer-encoding',
      'accept-encoding',
    ]);
    const forwardHeaders = {};
    for (const [k, v] of Object.entries(req.headers)) {
      const lower = k.toLowerCase();
      if (!skipHeaders.has(lower) && !lower.startsWith('x-vercel-') && !lower.startsWith('x-forwarded-')) {
        if (v !== undefined) forwardHeaders[k] = v;
      }
    }

    forwardHeaders['X-Original-Host'] = vercelHost;
    forwardHeaders['X-Forwarded-Host'] = vercelHost;
    forwardHeaders['X-Forwarded-Proto'] = 'https';

    const upstream = await fetch(targetUrl, {
      method: req.method,
      headers: forwardHeaders,
      body: rawBody,
      redirect: 'manual',
    });

    // Forward Set-Cookie headers so OJSSID session cookie is saved on odol-journal.vercel.app
    let setCookies = [];
    if (typeof upstream.headers.getSetCookie === 'function') {
      setCookies = upstream.headers.getSetCookie();
    } else if (upstream.headers.get('set-cookie')) {
      setCookies = [upstream.headers.get('set-cookie')];
    }
    if (setCookies.length > 0) {
      const cleanedCookies = setCookies.map((c) =>
        c
          .replace(/;\s*domain=[^;]+/gi, '')
          .replace(/;\s*path=[^;]+/gi, '; Path=/')
      );
      res.setHeader('Set-Cookie', cleanedCookies);
    }

    // Rewrite Location header on redirects so browser always stays on odol-journal.vercel.app
    const location = upstream.headers.get('location');
    if (location) {
      let newLoc = location;
      if (tunnelBase) {
        const tunnelHost = tunnelBase.replace(/^https?:\/\//, '');
        newLoc = newLoc.split(`https://${tunnelHost}`).join(`https://${vercelHost}`);
        newLoc = newLoc.split(`http://${tunnelHost}`).join(`https://${vercelHost}`);
      }
      newLoc = newLoc.replace(/^https?:\/\/localhost/i, `https://${vercelHost}`);
      res.setHeader('Location', newLoc);
    }

    const contentType = upstream.headers.get('content-type') || 'text/html; charset=utf-8';
    res.setHeader('Content-Type', contentType);
    const cacheControl = upstream.headers.get('cache-control');
    if (cacheControl) {
      res.setHeader('Cache-Control', cacheControl);
    }
    const contentDisposition = upstream.headers.get('content-disposition');
    if (contentDisposition) {
      res.setHeader('Content-Disposition', contentDisposition);
    }

    const arrayBuffer = await upstream.arrayBuffer();
    let buffer = Buffer.from(arrayBuffer);

    if (
      contentType.includes('text/html') ||
      contentType.includes('application/json') ||
      contentType.includes('text/css') ||
      contentType.includes('javascript')
    ) {
      let text = buffer.toString('utf8');
      if (tunnelBase) {
        const tunnelHost = tunnelBase.replace(/^https?:\/\//, '');
        text = text.split(tunnelHost).join(vercelHost);
      }
      text = text.replace(/https?:\/\/localhost\/ODOL/g, `https://${vercelHost}/ODOL`);
      text = text.replace(/\/\/localhost\/ODOL/g, `//${vercelHost}/ODOL`);
      buffer = Buffer.from(text, 'utf8');
    }

    res.status(upstream.status).send(buffer);
  } catch (err) {
    res.status(502).send(`OJS Proxy Error: ${err.message}`);
  }
};
