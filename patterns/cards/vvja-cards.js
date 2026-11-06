/**
 * @file
 * Pattern Cards: "2 / 4" with previous and next inside the open card. Focus
 * moves to the new card's header, because the bar it was on goes away.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

((Drupal) => {
  const P = Drupal.VvjaPattern;

  Drupal.behaviors.vvjPatternVvjaCards = P.behavior(
    'vvj-pattern-vvja-cards',
    '.vvj-pattern--vvja-cards',
    (root, signal) => {
      const items = P.items(root);
      if (items.length < 2) {
        return;
      }
      const total = items.length;
      let bar = null;
      // Undefined until hydrated, so the page never scrolls on load.
      let last;
      const go = (index) => {
        const target = (index + total) % total;
        P.open(root, target);
        requestAnimationFrame(() =>
          items[target].querySelector(':scope > .vvja-button')?.focus(),
        );
      };
      P.follow(
        root,
        (c) => {
          // The open card moves to the top of the grid: bring it into view
          // when the visitor opened it, so the page does not seem to jump.
          if (last !== undefined && c >= 0 && c !== last) {
            const still = window.matchMedia(
              '(prefers-reduced-motion: reduce)',
            ).matches;
            items[c].scrollIntoView({
              block: 'nearest',
              behavior: still ? 'auto' : 'smooth',
            });
          }
          last = c;
          bar?.remove();
          bar = null;
          const inner =
            c < 0 ? null : items[c].querySelector('.vvja-pane-inner');
          if (!inner) {
            return;
          }
          bar = document.createElement('div');
          bar.className = 'vvja-step';
          const count = document.createElement('p');
          count.className = 'vvja-count';
          const now = document.createElement('span');
          now.className = 'vvja-count-now';
          now.textContent = String(c + 1);
          count.append(now, ` / ${total}`);
          const prev = P.button(
            'vvja-btn vvja-prev',
            Drupal.t('Previous card'),
            'prev',
          );
          const next = P.button(
            'vvja-btn vvja-next',
            Drupal.t('Next card'),
            'next',
          );
          prev.addEventListener('click', () => go(c - 1), { signal });
          next.addEventListener('click', () => go(c + 1), { signal });
          bar.append(count, prev, next);
          inner.appendChild(bar);
        },
        signal,
      );
      signal.addEventListener('abort', () => bar?.remove(), { once: true });
    },
  );
})(Drupal);
