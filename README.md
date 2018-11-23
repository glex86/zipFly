## G-Lex's zipFly compression library

This PHP library helps you to create true zip64 archives that contains hundreds of thousands of files.
It is highly optimized for low memory and I/O usage even when you need to compress a large number of files.

### Story
I started to create a custom PHP based backup software that can accomplish my special requirements.
I've got 12 debian wheezy and jessie based linux servers with PHP 5.6 installed on it.
Originally I wanted to use the built-in zipArchive class to the compression purpose.
Unfortunately I have realized that the version shipped with the OS can't do this job.
When adding a large number of files to the zip archive I was running into memory limit problems as well as file descriptor limit issues.
The reason is that when a file is added to the zip, the source file will open and remain open until you close the entire zip file.
After I solved the file descriptor issue with closing and reopening the zip archive after a certain number of added files, I was attempting to compress some larger files.
I think you know... I faced another surprise.
The original zip format has file number and file size restrictions. Because the zipArchive version that I used did not support the zip64 extension to overcome these limits, so the created large zip files can not be extracted due to file corruption.
There was also lot of another problem with zipArchive: big I/O wasting, can not set different compression algorithm to different files, can not track the compression progress and more and more...
I tried to find another solution in the form of different PHP classes, but none was perfectly suited to this task.

This was the point where I decided to develop my own solution.

### Goals
- [x] Working standard ZIP compressor
- [x] Implement zip64 extension
- [x] Handle zip creation with a huge amount of file
- [x] Compress large files in small chunks
- [x] No unwanted I/O
- [x] Optimize for high speed
- [x] Optimize for low memory usage
- [x] Optimize for high speed
- [x] Selectable compression algorithm and level
- [x] Per file compression algorithm setting
- [x] After adding a new file, it is immediately compresses it and writes out the data
- [x] Easy to use
- [x] Error handling
- [x] Documented public interfaces
- [ ] Well documented source


### Ideas to be tested and reimplemented
- [ ] Streamable zip creation
- [ ] Switch between 32bit and 64bit mode
- [ ] Compress small files in one step
- [ ] Add file from string
- [ ] Print out debug informations
_Technically, these features were the basics of the finished class, but due to optimization and simplicity, they are not currently in the classroom._

### My motivation
I had to make my own class because I needed a functionality that I did not find in a ready-made library, and I did not find it effective and perfect way if i modify one of them.
I did not want to create a zip library with the same features as the hundreds of other zip classes.
I have studied many different open source zip compression libraries that were also fantastic in themselves.
By combining best techniques from these with my own ideas, I've created the zipFly64 library.

### Interestings of this library
- Using a way to easily define exception classes with multiple message and exception code pairs
- An abstract exception class that enable us to hide some levels from the default textual representation's backtrace log for the easier debugging
- Really small codebase
- Ability to print out the generated headers in a fancy form
- Custom created php stream filter to calculate the uncompressed input file size and the crc32 hash value, by examining the chunks readed by the compressor
- Data compression using stream filters attached to the read chain of the source file. Allowing to process the source file chunk-by-chunk

### Warnings & Limitations
This library and the associated files are non commercial product.
It should not have unexpected results. However if any damage is caused by this software the author can not be responsible.
The use of this software is at the risk of the user.



