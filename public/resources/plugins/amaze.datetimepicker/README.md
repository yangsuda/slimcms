# Amaze UI Datetime Picker
---

该项目来自 [bootstrap-datetimepicker](https://github.com/smalot/bootstrap-datetimepicker)，只修改了 `CSS` 文件，使用需要结合原来的的 `class` 和 Amaze UI 使用。

## [使用演示](http://amazeui.github.io/datetimepicker/docs/demo.html)

## 调用方法

**设置时间格式：`format` 选项**

```html
<input type="text" value="2015-02-15 21:05" id="datetimepicker" class="am-form-field">
```
```javascript
$('#datetimepicker').datetimepicker({
  format: 'yyyy-mm-dd hh:ii'
});
```

也可以通过 `data-date-format` 来设置时间格式

```html
<input type="text" value="2015-02-14 21:05" id="datetimepicker" data-date-format="yyyy-mm-dd hh:ii">
```
```javascript
$('#datetimepicker').datetimepicker();
```

**结合 Amaze UI 组件**

结合 Amaze UI Class `.am-input-group` 来实现组件样式，其中 Class `.date`、`.add-on`、`.icon-th` 都在原 JS 中有引用，使用时务必加上。

```html
<div class="am-input-group date" id="datetimepicker" data-date="12-02-2012" data-date-format="dd-mm-yyyy">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>
```
```javascript
$('#datetimepicker').datetimepicker();
```

**内联调用：**

```html
<div id="datetimepicker"></div>
```
```javascript
$('#datetimepicker').datetimepicker();
```

## 依赖和编译

依赖 Amaze UI `input-group``form`，克隆项目后在 Node.js 环境下，首先全局安装 Gulp：

```bash
npm install -g gulp
```

然后进入目录安装依赖：

```bash
npm install
```

接下来，执行 gulp：

```bash
gulp serve
```

## 插件配置

所有的配置参数都是对 `Date` 对象的处理，按照 ISO-8601 日期和时间的表示方法，设置你的时间格式 `format` :

* yyyy-mm-dd
* yyyy-mm-dd hh:ii
* yyyy-mm-ddThh:ii
* yyyy-mm-dd hh:ii:ss
* yyyy-mm-ddThh:ii:ssZ

### format

日期格式：接受`String`,默认为`mm/dd/yyyy`

日期格式组合: p, P, h, hh, i, ii, s, ss, d, dd, m, mm, M, MM, yy, yyyy.

ss(秒), ii(分), hh(小时), HH(小时), dd(天), mm(月),代表不足两位数，以0作为占位符（01-02）

* p : 小写时间分界点('am' or 'pm')
* P : 大写时间分界点('AM' or 'PM')
* s : 秒
* ss : 秒
* i : 分
* ii : 分
* h : 小时, 24小时格式
* hh : 小时, 24小时格式
* H : 小时, 12小时格式
* HH : 小时, 12小时格式
* d : 天
* dd : 天
* m : 月
* mm : 月
* M : 月份短文本表述
* MM : 月份短文本表述
* yy : 年, 2位数字表示
* yyyy : 年, 4位数字表示

### weekStart

设置开始星期几的选择：接受整数 `0 - 6`，默认为 `0`，0 (Sunday) 到 6 (Saturday)。

### startDate

设置时间开始参数：接受 `Date` 类型值，开始时间前面的日期将被设置为 `disabled`。

### endDate

设置结束时间参数：接受 `Date` 类型值，结束时间后面的日期将被设置为 `disabled`。

### daysOfWeekDisabled

禁用星期的列数：接受 `String` 或 `Array` 类型参数

- 默认值为 `'', []`
- 示例：设置周六和周日禁用: `daysOfWeekDisabled: '0,6'` 或者 `daysOfWeekDisabled: [0,6]`。

### autoclose

设置时间后自动关闭时间选择器面板，参数类型：`Boolean` 默认值为：`false` 不关闭。

### startView

视图模式，通过参数 `startView` 设置日历初始视图模式，可以接受 `Number` | `String` 类型：

- `0` 或者 `hour`: 显示分
- `1` 或者 `days`: 显示小时
- `2` 或者 `month`: 显示天（默认）
- `3` 或者 `year`: 显示月
- `4` 或者 `decade`: 显示年

### minView

最小视图模式，可以接受 `Number` | `String` 类型：

- `0` 或者 `hour`: 显示分（默认）
- `1` 或者 `days`: 显示小时
- `2` 或者 `month`: 显示天
- `3` 或者 `year`: 显示月
- `4` 或者 `decade`: 显示年

### maxView

最大视图模式，可以接受 `Number` | `String` 类型：

- `0` 或者 `hour`: 显示分
- `1` 或者 `days`: 显示小时
- `2` 或者 `month`: 显示天
- `3` 或者 `year`: 显示月
- `4` 或者 `decade`: 显示年（默认）

### todayBtn

是否显示按钮 Today ，点击 Today 会跳转到今日的时间，默认为 `false`

### todayHighlight

是否高亮当日的日期，默认为 `false`。

### keyboardNavigation

是否允许键盘选择时间，默认为 `true`。

### language

语言设置，默认使用英语 `'en'` ,要支持更多语言可以通过参考下面的 I18N 扩展。

### forceParse

是否强制解析 `<input>` 元素内的时间格式, 默认为: `true`

### minuteStep

设置分钟视图时间间隔数，默认为 `5`。


### pickerReferer : （弃用）

指定输入的 `<input>` 元素，默认为 `default`。

### pickerPosition

设置选择器的定位方式，接受 `String` 类型：

- `'bottom-right'` (默认)
- `'bottom-left'`
- `'top-right'`
- `'top-left'`

### viewSelect

时间更新设置，默认为选择分面板时更新时间，可以接受 `Number` | `String` 类型：

- `0` 或者 `hour`: 显示分（默认）
- `1` 或者 `days`: 显示小时
- `2` 或者 `month`: 显示天
- `3` 或者 `year`: 显示月
- `4` 或者 `decade`: 显示年

### showMeridian

是否显示 AM 和 PM 分隔小时面板。默认值为 `false`。

### initialDate

设置时间选择器初始化的时间值，默认情况下是现在，你可以指定其他时间，`initialDate` 接受 `Date` | `String`， 默认值为: `new Date()`。

### onRender

渲染日历时调用的函数，比如 `.disabled` 设置禁用日期。

```javascript
var nowTemp = new Date();
var now = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate(), 0, 0, 0, 0);
$('#date-end')
  .datetimepicker({
    onRender: function(date) {
      return date.valueOf() < now.valueOf() ? 'disabled' : '';
    }
  });
```

**组件结合**

```html
<div class="am-input-group date form_datetime-1">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>
```

**带有删除的组件结合**

```html
<div class="am-input-group date form_datetime-3" data-date="2015-02-14 14:45">
  <input size="16" type="text" value="" class="am-form-field" readonly>
  <span class="add-on am-input-group-label"><i class="icon-remove am-icon-close"></i></span>
  <span class="add-on am-input-group-label"><i class="icon-th am-icon-calendar"></i></span>
</div>
```

## 方法

### `.datetimepicker(options)`

初始化日期时间选择器。

### `remove`

移除日期时间选择面板。

```javascript
$('#datetimepicker').datetimepicker('remove');
```

### `show`

显示日期时间选择面板。

```javascript
$('#datetimepicker').datetimepicker('show');
```

### `hide`

隐藏日期时间选择面板。

```javascript
$('#datetimepicker').datetimepicker('hide');
```

### `update`

参数：

* currentDate (Date).

更新指定的时间。

```javascript
$('#datetimepicker').datetimepicker('update', new Date());
```

如果更新时间为现在时间，则不需要传值。

```javascript
$('#datetimepicker').datetimepicker('update');
```

### `setStartDate`

设置开始时间，小于开始时间的则不能选中并设置 `disabled`，`setStartDate` 接受：`String` 值。

```javascript
$('#datetimepicker').datetimepicker('setStartDate', '2015-01-01');
```

如果忽略该值，将不启用该选项。

```javascript
$('#datetimepicker').datetimepicker('setStartDate');
$('#datetimepicker').datetimepicker('setStartDate', null);
```

### `setEndDate`

设置结束时间，大于结束时间的则不能选中并设置 `disabled` ，`setEndDate` 接受：`String` 值。

```javascript
$('#datetimepicker').datetimepicker('setEndDate', '2014-12-31');
```

如果忽略该值，选项无效。

```javascript
$('#datetimepicker').datetimepicker('setEndDate');
$('#datetimepicker').datetimepicker('setEndDate', null);
```

### `setDaysOfWeekDisabled`

禁用星期的列数：`setDaysOfWeekDisabled` 接受 `String` 或 `Array` 类型参数。

```javascript
// 周日和周六将被禁用
$('#datetimepicker').datetimepicker('setDaysOfWeekDisabled', [0,6]);
```

如果忽略该值，选项无效。

```javascript
$('#datetimepicker').datetimepicker('setDaysOfWeekDisabled');
$('#datetimepicker').datetimepicker('setDaysOfWeekDisabled', null);
```

## 自定义事件

### `show`

时间选择器显示时触发。

```javascript
$('#date-end')
  .datetimepicker()
  .on('show', function(ev){
    console.log('datetimepciker 显示了');
  });
```

### `hide`

时间选择器隐藏时触发。

```javascript
$('#date-end')
  .datetimepicker()
  .on('hide', function(ev){
    console.log('datetimepciker 将隐藏');
  });
```

### `changeDate`

时间日期发生修改时触发，通过 `ev.date` 获取修改后的时间。

```javascript
$('#date-end')
  .datetimepicker()
  .on('changeDate', function(ev){
    if (ev.date.valueOf() < date-start-display.valueOf()){
      ....
    }
  });
```

### `changeYear`

年份修改时触发。

### `changeMonth`

月份修改时触发。

### `outOfRange`

当选择了 `startDate` 前的时间和 `endDate` 后面的时间将触发该事件，`setDate`  `setUTCDate` 也会触发。


## 键盘控制导航

### up, down, left, right arrow keys

- 上、下、左、右键控制选择日期。

- `Shift` + 上或者左向前移动一个月，`Shift` + 下或右向后移动一个月。

- `Ctrl` + 上或者左向前移动一个年，`Ctrl` + 下或右向后移动一个年。

### escape

`ESC` 键退出激活的时间选择器。

### enter

`Enter` 回车键能够选择日期。

## 鼠标滚轮导航

### 依赖

支持鼠标滚轮导航需要依赖 [jQuery Mouse Wheel Plugin](https://github.com/brandonaaron/jquery-mousewheel)

### 配置参数

#### wheelViewModeNavigation

是否支持使用鼠标滚轮浏览不同的视图模式，`wheelViewModeNavigation` 默认为 `false`。

#### wheelViewModeNavigationInverseDirection

是否反向滚动, 默认的是向上滚动来查看，默认为：`false`

#### wheelViewModeNavigationDelay

设置面板滚动时间间距，`wheelViewModeNavigationDelay` 默认值为 `100`。

### Demo

支持鼠标滚轮控制器的[Demo](http://lyonlai.github.io/bootstrap-datetimepicker/demo.html)

## I18N 国际化

扩展语言支持, [其他语言扩展示例Demo](https://github.com/smalot/bootstrap-datetimepicker/tree/master/js/locales)

```javascript
$.fn.datetimepicker.dates['zh-CN'] = {
  days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
  daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六", "周日"],
  daysMin:  ["日", "一", "二", "三", "四", "五", "六", "日"],
  months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
  monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
  today: "今天",
  suffix: [],
  meridiem: ["上午", "下午"],
  rtl: true // 从右向左书写的语言你可以使用 rtl: true 来设置
};

// 调用

$('.xxx').datetimepicker({
  language:  'zh-CN'
});
```

你可以在格式属性的语言配置重写默认的日期格式。

```javascript
$.fn.datetimepicker.dates['pt-BR'] = {
  format: 'dd/mm/yyyy'
};
```

调用你扩展的语言插件，注意字符编码格式:

```html
<script type="text/javascript" src="datetimepicker.zh.js" charset="UTF-8"></script>
```
