/**
 * @file
 * Pattern Stage: autoplay through the steps (Drupal.Vvj.Autoplay), a
 * visible Pause / Play button and a counter. A step the visitor picks stops
 * autoplay for good; hover and focus hold it; reduced motion starts it
 * stopped.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

((Drupal) => {
  const P = Drupal.VvjaPattern;
  // Milliseconds per step. A stored setting for it is an open decision.
  const DURATION = 7000;

  Drupal.behaviors.vvjPatternVvjaStage = P.behavior(
    'vvj-pattern-vvja-stage',
    '.vvj-pattern--vvja-stage',
    (root, signal) => {
      const items = P.items(root);
      if (items.length < 2) {
        return;
      }
      const total = items.length;
      const controls = document.createElement('div');
      controls.className = 'vvja-controls';
      const play = document.createElement('button');
      play.type = 'button';
      play.className = 'vvja-play';
      const label = document.createElement('span');
      label.className = 'vvj-autoplay-label';
      // Named from the start: the button is in the page before the element
      // hydrates, and Autoplay's render() only sets the label after that.
      // "Pause" matches the icon CSS shows until the stopped class is set.
      label.textContent = Drupal.t('Pause');
      play.append(P.icon('pause'), P.icon('play'), label);
      const count = document.createElement('p');
      count.className = 'vvja-count';
      const now = document.createElement('span');
      now.className = 'vvja-count-now';
      count.append(now, ` / ${total}`);
      controls.append(play, count);
      root.appendChild(controls);

      Drupal.Vvj.whenReady(
        root,
        () => {
          const autoplay = new Drupal.Vvj.Autoplay(root, {
            duration: DURATION,
            button: play,
            signal,
            onTick: () => P.open(root, P.current(root) + 1),
          });
          items.forEach((item) => {
            item.querySelector(':scope > .vvja-button')?.addEventListener(
              'click',
              (event) => {
                if (event.isTrusted) {
                  autoplay.stop();
                }
              },
              { signal },
            );
          });
          play.addEventListener(
            'click',
            () => {
              if (!autoplay.stopped && P.current(root) < 0) {
                P.open(root, 0);
              }
            },
            { signal },
          );
        },
        signal,
      );
      P.follow(
        root,
        (c) => {
          now.textContent = String(c < 0 ? 0 : c + 1);
        },
        signal,
      );
      signal.addEventListener('abort', () => controls.remove(), { once: true });
    },
  );
})(Drupal);
