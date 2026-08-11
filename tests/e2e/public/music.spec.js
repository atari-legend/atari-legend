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

  // /music/{sndh} proxies an outbound request to sndhrecord.atari.org, so it
  // cannot be tested here without making the suite depend on a third party.
  // Extract that host to config first (tests/e2e/README.md, follow-up 4).
  //
  // TODO: the SNDH player itself - ym2149-wasm loading and starting playback
  // is the part most likely to break silently, and it is pure browser work,
  // which is exactly what an e2e test is for.
});
