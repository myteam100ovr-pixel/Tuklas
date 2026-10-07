const DEFAULT_RAILWAY_ORIGIN = 'https://tuklasprojectt.up.railway.app';

export default {
    async fetch(request, env) {
        const publicUrl = new URL(request.url);
        const railwayOrigin = env.RAILWAY_ORIGIN || DEFAULT_RAILWAY_ORIGIN;
        const originUrl = new URL(railwayOrigin);
        const upstreamUrl = new URL(`${publicUrl.pathname}${publicUrl.search}`, originUrl);
        const upstreamRequest = new Request(upstreamUrl, request);
        const headers = new Headers(upstreamRequest.headers);

        headers.set('X-Forwarded-Host', publicUrl.host);
        headers.set('X-Forwarded-Proto', publicUrl.protocol.slice(0, -1));

        const clientIp = request.headers.get('CF-Connecting-IP');
        if (clientIp) {
            headers.set('X-Forwarded-For', clientIp);
        }

        const response = await fetch(new Request(upstreamRequest, {
            headers,
            redirect: 'manual',
        }));

        const location = response.headers.get('Location');
        if (location) {
            const redirectUrl = new URL(location, upstreamUrl);
            if (redirectUrl.origin === originUrl.origin) {
                redirectUrl.protocol = publicUrl.protocol;
                redirectUrl.host = publicUrl.host;

                const responseHeaders = new Headers(response.headers);
                responseHeaders.set('Location', redirectUrl.toString());

                return new Response(response.body, {
                    status: response.status,
                    statusText: response.statusText,
                    headers: responseHeaders,
                });
            }
        }

        return response;
    },
};
