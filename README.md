# Views Vanilla JavaScript Accordion

## Overview

The **Views Vanilla JavaScript Accordion** module (machine name: `vvja`) is a
versatile and lightweight Views style plugin designed to render items in an
accordion format using vanilla JavaScript. This module enhances the user
experience by providing smooth, collapsible navigation for content displayed
through Drupal Views, eliminating the need for external JavaScript libraries
like jQuery UI or Bootstrap.

## Features

- **Pure JavaScript**: Utilizes vanilla JavaScript for efficient performance
  and reduced dependencies, offering a modern approach to creating interactive
  content displays.
- **Toggle Options**: Choose between single toggle indicators for each section
  or a global toggle for expanding/collapsing all sections at once.
- **First Item Expansion**: Option to automatically expand the first section by
  default, improving initial user interaction.
- **Animation Effects**: Select from various animation styles, including Top,
  Bottom, Left, Right, Zoom, and Opacity, for engaging transitions.
- **Unique ID Generation**: Automatically generates a unique numeric ID for
  each view display to ensure consistency and prevent conflicts.
- **CSS Customization**: Easily customize the appearance of the accordion with
  optional CSS inclusion to match your site’s design.

## Accessibility

The **VVJA** module includes several accessibility features to ensure that your
accordion is usable by all visitors, including those using screen readers:

- **ARIA Roles and Properties**: Proper ARIA roles and properties provide
  context and state information to screen readers.
- **Keyboard Navigation**: Users can navigate through accordion sections using
  keyboard shortcuts, ensuring full functionality without a mouse.
- **Focus Management**: Ensures that the currently displayed section is focused,
  providing a better experience for keyboard and screen reader users.

## Configuration

After installing and enabling the module, you can configure it through the
Views UI:

1. **Toggle Settings**: Configure the single and global toggle options to
   control how sections are expanded and collapsed.
2. **First Item Expansion**: Enable this option to have the first section
   expanded by default.
3. **Animation Type**: Choose the desired animation effect for the accordion
   transitions.
4. **Enable CSS**: Optionally enable custom CSS for styling the accordion.
   When enabled, additional styling options are available.

## Example Usage

1. Create a new view or edit an existing view.
2. Under the "Format" section, select "Views Vanilla JavaScript Accordion" as
   the style.
3. Configure the toggle settings, animation type, and CSS options as needed.
4. Save the view and see the accordion navigation in action on the front end.

## Additional Information

- **Dependencies**: This module does not have any dependencies on other
  contributed modules.
- **Compatibility**: Fully compatible with Drupal 10 and follows Drupal best
  practices.

## Installation

1. Download and install the module from
   [Drupal.org](https://www.drupal.org/project/vvja).
2. Enable the module through the Drupal admin interface or using Drush: 
   `drush en vvja`
3. Clear caches to ensure the new plugin is available: `drush cr`

## Troubleshooting

If you encounter any issues or have suggestions for improvements, please open
an issue in the module's issue queue on Drupal.org.

## Maintainers

- [Alaa Haddad](https://www.drupal.org/u/flashwebcenter)

## Support

For issues, feature requests, and general support, please use the 
[issue queue](https://www.drupal.org/project/issues/vvja).

## License

This project is licensed under the 
[GNU General Public License, version 2 or later](http://www.gnu.org/licenses/gpl-2.0.html).

---

This documentation adheres to Drupal best practices, providing a clear and
comprehensive guide for users to understand the module's functionality,
features, and setup. Users are encouraged to refer to the issue queue for
support and contributions.
