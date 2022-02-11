#!/usr/bin/env python3
#-*- coding: utf-8 -*-


"""
Python 版本语法
"""

# 对象展开和列表展开 Object Spread Operator for Python
# https://sparrow.dev/object-spread-operator-python/
# Python3.5+
old_dict = {'hello': 'world', 'foo': 'bar'}
new_dict = {**old_dict, 'foo': 'baz'}

old_list = [1, 2, 3]
new_list = [*old_list, 4, 5]
