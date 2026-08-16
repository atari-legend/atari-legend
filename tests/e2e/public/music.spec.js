import { test } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectResourceLoads } from '../support/assertions.js';

test.describe('Music', () => {
  test('serves a game music cover', async ({ page }) => {
    const path = `/music/cover/${FIXTURE.game.slug}`;

    // The cover is squared with GD and re-encoded, but Intervention's
    // response() keeps the source format, so a PNG screenshot comes back PNG.
    await expectResourceLoads(await page.request.get(path), path, {
      contentType: 'image/png',
      magic: 'PNG',
    });
  });

  // /music/{sndh} is deliberately not here. It proxies an outbound request,
  // and the host it proxies from is now config('al.sndh.mp3_base_url') rather
  // than a literal - so a spec *could* point it somewhere local. There is
  // nothing left for one to prove: ResourceControllersTest fakes the HTTP
  // client and already covers the URL it composes, the subtune padding and a
  // 404 coming back. Driving the same three cases through a browser would only
  // be slower.
  //
  // TODO: the SNDH player itself - ym2149-wasm loading and starting playback
  // is the part most likely to break silently, and it is pure browser work,
  // which is exactly what an e2e test is for.
});
