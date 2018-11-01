## G-Lex's zipFly compression library

This PHP library helps you to create true zip64 archives on-the-fly without unwanted I/O operations.
It compresses and directly write out the compresses data immediately after you added to the archive.

### Benefits over zipArchive

This class is designed to create archives that contains a lot of thousand files.
- Lower memory usage
- Lower IO overhead
- Support zip64
- Different compression settings to individual files

