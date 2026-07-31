#!/usr/bin/env python3
"""
Remove background from an image using rembg (AI-based).

Usage:
    python remove_bg.py <input_path> <output_path>
"""

import sys
import types

# ── Workarounds for Windows subprocess environment ────────────────
# When PHP spawns Python, certain system providers aren't available.

# 1) Stub _overlapped (asyncio/Winsock not initialised in subprocess)
try:
    import _overlapped
except (OSError, ImportError):
    sys.modules['_overlapped'] = types.ModuleType('_overlapped')

# 2) Stub paramiko (pooch imports it; its cryptography/OpenSSL call fails
#    in subprocess environment with "entropy source strength too weak")
sys.modules['paramiko'] = types.ModuleType('paramiko')

# 3) Set U2NET_HOME — prefer home dir, fall back to /tmp if not writable (Docker www-data)
import os
_home_u2net = os.path.join(os.path.expanduser('~'), '.u2net')
_tmp_u2net = os.path.join('/tmp', '.u2net')
# Use home if model already exists there (pre-downloaded), else use /tmp
if os.path.isfile(os.path.join(_home_u2net, 'u2net.onnx')):
    os.environ.setdefault('U2NET_HOME', _home_u2net)
else:
    os.environ.setdefault('U2NET_HOME', _tmp_u2net)

# 4) Set numba cache dir to /tmp (Docker: site-packages may be read-only)
os.environ.setdefault('NUMBA_CACHE_DIR', os.path.join(os.environ.get('TMPDIR', '/tmp'), 'numba_cache'))

# ── Now safe to import rembg ──────────────────────────────────────
from pathlib import Path
from rembg import remove


def main():
    if len(sys.argv) != 3:
        print("Usage: python remove_bg.py <input_path> <output_path>", file=sys.stderr)
        sys.exit(1)

    input_path = Path(sys.argv[1])
    output_path = Path(sys.argv[2])

    if not input_path.exists():
        print(f"Input file not found: {input_path}", file=sys.stderr)
        sys.exit(1)

    # Read input image
    input_data = input_path.read_bytes()

    # Remove background (returns PNG bytes with soft alpha mask)
    output_data = remove(input_data)

    # Post-process: threshold the alpha channel to make the product fully opaque.
    # rembg produces soft edges where the product itself gets semi-transparent.
    # Pixels with alpha > 50 → fully opaque (255), otherwise → fully transparent (0).
    from PIL import Image
    import io
    img = Image.open(io.BytesIO(output_data)).convert('RGBA')
    r, g, b, a = img.split()
    # Threshold: alpha > 50 → 255, else → 0
    a = a.point(lambda x: 255 if x > 50 else 0)
    img = Image.merge('RGBA', (r, g, b, a))

    # Ensure output directory exists
    output_path.parent.mkdir(parents=True, exist_ok=True)

    # Write result as PNG
    img.save(str(output_path), 'PNG')
    print(f"OK:{output_path}", flush=True)


if __name__ == "__main__":
    main()
