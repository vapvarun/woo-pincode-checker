---
title: Styling
description: Match your theme or use a custom accent color for the checker.
---

# Styling

Go to **Display and Messages > Style**.

| Setting | Default | Options |
|---------|---------|---------|
| Colours | Match my theme | **Match my theme** or **Use my accent colour** |
| Accent colour | `#2271b1` | Any hex color |

The accent colour is used for the **Check** button when **Use my accent colour** is selected. It is set as the CSS variable `--wbpc-accent` on the `.wbpc-checker` wrapper, so you can also override it in your theme CSS:

```css
.wbpc-checker {
	--wbpc-accent: #b32d2e;
}
```

For deeper changes, [override the template](04-template-override.md).
