#!/usr/bin/env python3
#-*- coding: utf-8 -*-


"""
pydoc 注释示例 https://stackoverflow.com/questions/34331088/how-to-comment-parameters-for-pydoc
"""

"""
This example module shows various types of documentation available for use
with pydoc.  To generate HTML documentation for this module issue the
command:

    pydoc -w foo

"""

class Foo(object):
    """
    Foo encapsulates a name and an age.
    """
    def __init__(self, name, age):
        """
        Construct a new 'Foo' object.

        :param name: The name of foo
        :param age: The ageof foo
        :return: returns nothing
        """
        self.name = name
        self.age

def bar(baz):
    """
    Prints baz to the display.
    """
    print( baz)

if __name__ == '__main__':
    f = Foo('John Doe', 42)
    bar("hello world")