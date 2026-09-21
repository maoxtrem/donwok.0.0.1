/**
 * Cliente genérico para suscripciones Mercure desde el navegador.
 *
 * Las vistas solo deben conocer sus topics y reaccionar a los eventos;
 * el endpoint y el formato de consulta quedan centralizados aquí.
 */
export default class MercureClient {
  constructor({ endpoint = '/.well-known/mercure', eventSourceFactory = EventSource } = {}) {
    this.endpoint = endpoint;
    this.eventSourceFactory = eventSourceFactory;
  }

  subscribe({ topics, onOpen, onMessage, onError, options } = {}) {
    const normalizedTopics = Array.isArray(topics) ? topics.filter(Boolean) : [topics].filter(Boolean);

    if (normalizedTopics.length === 0) {
      throw new Error('MercureClient necesita al menos un topic para suscribirse.');
    }

    const url = new URL(this.endpoint, window.location.origin);
    normalizedTopics.forEach((topic) => url.searchParams.append('match', topic));

    const eventSource = new this.eventSourceFactory(url, options);

    if (onOpen) eventSource.addEventListener('open', onOpen);
    if (onMessage) {
      eventSource.addEventListener('message', (event) => {
        try {
          onMessage(JSON.parse(event.data), event);
        } catch (error) {
          console.error('[Mercure] Evento inválido:', error, event.data);
        }
      });
    }
    if (onError) eventSource.addEventListener('error', onError);

    return {
      url,
      close: () => eventSource.close(),
      eventSource,
    };
  }
}
