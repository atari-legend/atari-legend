import { test } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectResourceLoads } from '../support/assertions.js';

test.describe('Music', () => {
  test.fixme('serves a game music cover', async ({ page }) => {
    // Squares the game screenshot with GD and encodes WebP, which the docker
    // dev image's PNG-only GD cannot do. See tests/e2e/README.md.
    const path = `/music/cover/${FIXTURE.game.slug}`;

    await expectResourceLoads(await page.request.get(path), path, {
      contentType: 'image/webp',
      magic: 'WEBP',
    });
  });

  // /music/{sndh} proxies an outbound request to sndhrecord.atari.org, so it
  // cannot be tested here without making the suite depend on a third party.
  // Extract that host to config first (tests/e2e/README.md, follow-up 5).
  //
  // TODO: the SNDH player itself - ym2149-wasm loading and starting playback
  // is the part most likely to break silently, and it is pure browser work,
  // which is exactly what an e2e test is for.
});
