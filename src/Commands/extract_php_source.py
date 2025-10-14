#!/usr/bin/env python3
"""
Windows-compatible PHP source extraction script.
This script properly handles .tar.xz and .tar.gz extraction on Windows where the piped
7za + tar method fails silently.
"""

import tarfile
import lzma
import gzip
import os
import sys
import shutil

def extract_tar_archive(archive_path, destination, strip_components=1):
    """
    Extract a .tar.xz, .tar.gz, or .tgz archive with strip-components support.

    Args:
        archive_path: Path to the tar archive file
        destination: Destination directory
        strip_components: Number of leading path components to strip

    Returns:
        bool: True if successful, False otherwise
    """
    try:
        # Ensure destination exists
        os.makedirs(destination, exist_ok=True)

        # Detect archive type and open accordingly
        if archive_path.endswith('.tar.xz') or archive_path.endswith('.txz'):
            file_handle = lzma.open(archive_path, 'rb')
        elif archive_path.endswith('.tar.gz') or archive_path.endswith('.tgz'):
            file_handle = gzip.open(archive_path, 'rb')
        else:
            # Try to open as plain tar
            file_handle = open(archive_path, 'rb')

        with file_handle:
            with tarfile.open(fileobj=file_handle, mode='r|') as tar:
                # Extract with strip-components logic
                for member in tar:
                    # WINDOWS FIX: Skip symlinks as Windows cannot create them without admin privileges
                    if member.issym() or member.islnk():
                        print(f"Skipping symlink: {member.name} (Windows compatibility)")
                        continue

                    # Split path and remove leading components
                    parts = member.name.split('/', strip_components)

                    if len(parts) > strip_components:
                        # Reconstruct path without leading components
                        member.name = '/'.join(parts[strip_components:])

                        # Skip if name is empty after stripping
                        if not member.name:
                            continue

                        # Extract the member
                        tar.extract(member, destination)

        return True

    except Exception as e:
        print(f"Error extracting archive: {e}", file=sys.stderr)
        return False

def main():
    """Main entry point for the extraction script."""
    if len(sys.argv) < 3:
        print("Usage: python extract_php_source.py <archive_path> <destination> [strip_components]")
        print("Example: python extract_php_source.py php-8.3.26.tar.xz ./php-src 1")
        print("Example: python extract_php_source.py sqlsrv.tgz ./ext/sqlsrv 1")
        sys.exit(1)

    archive_path = sys.argv[1]
    destination = sys.argv[2]
    strip_components = int(sys.argv[3]) if len(sys.argv) > 3 else 1

    if not os.path.exists(archive_path):
        print(f"Error: Archive not found: {archive_path}", file=sys.stderr)
        sys.exit(1)

    print(f"Extracting {archive_path} to {destination} (strip-components={strip_components})...")

    success = extract_tar_archive(archive_path, destination, strip_components)

    if success:
        print("[OK] Extraction completed successfully!")
        sys.exit(0)
    else:
        print("[FAILED] Extraction failed!", file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    main()