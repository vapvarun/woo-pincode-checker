---
title: Testing a postcode
description: See what a shopper sees for any postcode, and which area decided it.
---

# Testing a postcode

1. Go to **WB Plugins > Pincode Checker > Overview**.
2. In **Test a postcode**, type a postcode.
3. Pick the country. It starts on your store country.
4. Click **Test**.

The result shows the message a shopper sees and the area that decided it. Use it to confirm a prefix, a range or a blocked area works as you expect.

## Tips

- Test the edges of a range, such as `110001` and `110099`.
- Test a postcode covered by both a prefix and an exact area to see precedence in action.
- If you changed areas directly in the database and the result looks old, go to **Tools** and click **Clear lookup cache**.

You can run the same check from the command line with `wp wbpc check 110001`. See the [developer guide](../developer-guide/03-wp-cli.md).
