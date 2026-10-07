const DEFAULT_RAILWAY_ORIGIN = 'https://tuklasprojectt.up.railway.app';
const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'poster'];

class RewriteRailwayUrls {
    constructor(railwayOrigin, publicUrl) {
        this.railwayOrigin = railwayOrigin;
        this.publicUrl = publicUrl;
    }

    element(element) {
        for (const attribute of URL_ATTRIBUTES) {
            const value = element.getAttribute(attribute);
            if (!value) {
                continue;
            }

            let targetUrl;
            try {
                targetUrl = new URL(value, this.railwayOrigin);
            } catch {
                continue;
            }

            if (targetUrl.origin !== this.railwayOrigin.origin) {
                continue;
            }

            targetUrl.protocol = this.publicUrl.protocol;
            targetUrl.host = this.publicUrl.host;
            element.setAttribute(attribute, targetUrl.toString());
        }
    }
}

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

        const contentType = response.headers.get('Content-Type') || '';
        if (contentType.includes('text/html')) {
            return new HTMLRewriter()
                .on('*', new RewriteRailwayUrls(originUrl, publicUrl))
                .transform(response);
        }

        return response;
    },
};
