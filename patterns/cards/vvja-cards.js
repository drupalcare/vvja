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
      // The grid row a card sits on while every card is closed. The open card
      // is placed on that row, so it grows from where it was clicked instead
      // of moving away; the cards that shared the row move below it (dense).
      // Counted from the card's place in the list, never from the current
      // layout, which an open card has already rearranged.
      let row = 0;
      const rowOf = (item) => {
        const cols =
          getComputedStyle(item.parentElement)
            .gridTemplateColumns.split(' ')
            .filter((track) => track && track !== 'none').length || 1;
        return Math.floor(items.indexOf(item) / cols) + 1;
      };
      root.addEventListener(
        'click',
        (event) => {
          // Previous and next open a card through a scripted click; the open
          // card keeps the row it is on, so only a visitor's click moves it.
          if (!event.isTrusted) {
            return;
          }
          const button = event.target.closest('.vvja-button');
          const item = button?.parentElement;
          if (
            item &&
            items.includes(item) &&
            !item.classList.contains('opened')
          ) {
            row = rowOf(item);
          }
        },
        { capture: true, signal },
      );
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
          items.forEach((item, i) => {
            item.style.gridRowStart = i === c && row ? String(row) : '';
          });
          // Previous and next keep the open card on the same row.
          // Bring it into view only when its top is off screen.
          if (
            last !== undefined &&
            c >= 0 &&
            c !== last &&
            items[c].getBoundingClientRect().top < 0
          ) {
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
