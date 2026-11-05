/**
 * @file
 * Pattern Agenda: a Copy link button in each item (its deep link) and a
 * sticky dock of numbers that follows the scroll and opens an item.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

((Drupal) => {
  const P = Drupal.VvjaPattern;

  Drupal.behaviors.vvjPatternVvjaAgenda = P.behavior(
    'vvj-pattern-vvja-agenda',
    '.vvj-pattern--vvja-agenda',
    (root, signal) => {
      const items = P.items(root);
      if (!items.length) {
        return;
      }
      const added = [];
      const timers = new Set();
      const id = root.dataset.deeplinkId;

      // Copy link: only when the view gives every item an address.
      if (id && navigator.clipboard) {
        items.forEach((item, i) => {
          const inner = item.querySelector('.vvja-pane-inner');
          if (!inner) {
            return;
          }
          const copy = document.createElement('button');
          copy.type = 'button';
          copy.className = 'vvja-copy';
          const text = document.createElement('span');
          text.textContent = Drupal.t('Copy link');
          copy.append(P.icon('link'), P.icon('done'), text);
          copy.addEventListener(
            'click',
            () => {
              const base = window.location.href.split('#')[0];
              navigator.clipboard.writeText(`${base}#accordion-${id}-${i + 1}`).then(
                () => {
                  copy.classList.add('is-done');
                  text.textContent = Drupal.t('Link copied');
                  Drupal.announce(Drupal.t('Link copied'));
                  const t = setTimeout(() => {
                    timers.delete(t);
                    copy.classList.remove('is-done');
                    text.textContent = Drupal.t('Copy link');
                  }, 2000);
                  timers.add(t);
                },
                () => Drupal.announce(Drupal.t('The link could not be copied')),
              );
            },
            { signal },
          );
          (inner.querySelector('.vvj-body') || inner).appendChild(copy);
          added.push(copy);
        });
      }

      // The dock: one chip per item, showing its visible number.
      const dock = document.createElement('nav');
      dock.className = 'vvja-dock';
      dock.setAttribute('aria-label', Drupal.t('Jump to an item'));
      const track = document.createElement('div');
      track.className = 'vvja-dock-track';
      const chips = items.map((item, i) => {
        const label =
          item.querySelector('.vvj-time')?.textContent.trim() || String(i + 1);
        const title = item.querySelector('.vvj-title')?.textContent.trim() || label;
        const chip = P.button('vvja-chip', title);
        chip.textContent = label;
        chip.addEventListener(
          'click',
          () => {
            P.open(root, i);
            item.scrollIntoView({
              block: 'start',
              behavior: Drupal.Vvj.reducedMotion() ? 'auto' : 'smooth',
            });
          },
          { signal },
        );
        track.appendChild(chip);
        return chip;
      });
      dock.appendChild(track);
      root.appendChild(dock);
      added.push(dock);

      const mark = (index) => {
        chips.forEach((chip, i) => {
          chip.setAttribute('aria-current', String(i === index));
        });
      };
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                mark(items.indexOf(/** @type {HTMLElement} */ (entry.target)));
              }
            });
          },
          { rootMargin: '-40% 0px -55% 0px' },
        );
        items.forEach((item) => observer.observe(item));
        signal.addEventListener('abort', () => observer.disconnect(), {
          once: true,
        });
      }
      P.follow(
        root,
        (c) => {
          if (c >= 0) {
            mark(c);
          }
        },
        signal,
      );

      signal.addEventListener(
        'abort',
        () => {
          timers.forEach(clearTimeout);
          added.forEach((el) => el.remove());
        },
        { once: true },
      );
    },
  );
})(Drupal);
