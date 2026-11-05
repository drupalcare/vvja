/**
 * @file
 * Pattern Spotlight: the bottom bar (counter, one segment per panel,
 * previous and next). With JS off the panels still open from their headers.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

((Drupal) => {
  const P = Drupal.VvjaPattern;

  Drupal.behaviors.vvjPatternVvjaSpotlight = P.behavior(
    'vvj-pattern-vvja-spotlight',
    '.vvj-pattern--vvja-spotlight',
    (root, signal) => {
      const items = P.items(root);
      if (items.length < 2) {
        return;
      }
      const total = items.length;
      const pad = (n) => Drupal.Vvj.formatCount(n, total, { pad: true });
      const nav = document.createElement('div');
      nav.className = 'vvja-nav';
      const count = document.createElement('p');
      count.className = 'vvja-count';
      const now = document.createElement('span');
      now.className = 'vvja-count-now';
      count.append(now, ` / ${pad(total)}`);
      const bars = document.createElement('div');
      bars.className = 'vvja-bars';
      bars.setAttribute('role', 'group');
      bars.setAttribute('aria-label', Drupal.t('Panels'));
      items.forEach((item, i) => {
        const title =
          item.querySelector('.vvj-title')?.textContent.trim() || String(i + 1);
        const bar = P.button(
          'vvja-bar',
          Drupal.t('Show @title', { '@title': title }),
        );
        bar.addEventListener('click', () => P.open(root, i), { signal });
        bars.appendChild(bar);
      });
      const arrows = document.createElement('div');
      arrows.className = 'vvja-arrows';
      const prev = P.button('vvja-btn vvja-prev', Drupal.t('Previous'), 'prev');
      const next = P.button('vvja-btn vvja-next', Drupal.t('Next'), 'next');
      arrows.append(prev, next);
      nav.append(count, bars, arrows);
      root.appendChild(nav);
      const step = (d) => {
        const c = P.current(root);
        P.open(root, c < 0 ? 0 : c + d);
      };
      prev.addEventListener('click', () => step(-1), { signal });
      next.addEventListener('click', () => step(1), { signal });
      P.follow(
        root,
        (c) => {
          now.textContent = pad(c + 1);
          Array.from(bars.children).forEach((bar, i) => {
            bar.setAttribute('aria-current', String(i === c));
          });
        },
        signal,
      );
      signal.addEventListener('abort', () => nav.remove(), { once: true });
    },
  );
})(Drupal);
