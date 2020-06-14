<template>
  <van-cell-group class="cell-group card-group-box">
    <van-cell
      class="cell"
      value-class="fs-12 text-secondary"
      :is-link="isLink"
      v-if="showHead"
      :to="to"
    >
      <van-icon
        slot="icon"
        class="van-cell__left-icon left-icon"
        :name="icon"
        :style="iconStyle"
        v-if="icon"
      />
      <div :style="titleStyle" slot="title">{{title}}</div>
      <div :style="valueStyle">{{value}}</div>
      <slot name="headRight"/>
    </van-cell>
    <div class="cell-panel" :class="colsClass" ref="cellPanel">
      <div
        class="item"
        v-for="(item,index) in items"
        :key="index"
        :class="itemClass(index)"
        v-show="!itemShow(index)"
      >
        <router-link class="box van-hairline--right" tag="div" :to="item.link?item.link:''">
          <div class="title" :style="itemTitleStyle">{{item.title}}</div>
          <div class="text" :style="itemTextStyle">{{item.text}}</div>
        </router-link>
      </div>
    </div>
    <div class="foot" v-if="showFoot">
      <van-icon
        class="icon"
        :class="show?'upward':'down'"
        name="v-icon-downs"
        color="#909399"
        @click="open"
      />
    </div>
  </van-cell-group>
</template>

<script>
export default {
  data() {
    return {
      show: false
    };
  },
  props: {
    items: {
      type: Array
    },
    showHead: {
      type: Boolean,
      default: true
    },
    title: {
      type: String
    },
    titleStyle: {
      type: Object
    },
    icon: {
      type: String
    },
    iconStyle: {
      type: Object
    },
    value: {
      type: String
    },
    valueStyle: {
      type: Object
    },
    itemTitleStyle: {
      type: Object
    },
    itemTextStyle: {
      type: Object
    },
    isLink: {
      type: Boolean,
      default: false
    },
    to: {
      type: String
    },
    cols: {
      type: [String, Number],
      default: 0
    },
    showAll: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    colsClass() {
      return this.cols ? "cell-panel-" + this.cols : null;
    },
    showFoot() {
      return !this.showAll && this.cols && this.items.length > this.cols;
    }
  },
  methods: {
    open() {
      if (this.showFoot) {
        this.show = !this.show;
      }
    },
    itemClass(index) {
      if (this.show && this.cols && index < this.cols)
        return "van-hairline--bottom";
    },
    itemShow(index) {
      if (this.showAll) return false;
      if (this.show) return false;
      if (this.cols && index >= this.cols) return true;
    }
  }
};
</script>

<style scoped>
.cell-group {
  margin-bottom: 10px;
}

.cell-panel {
  display: flex;
  padding: 10px 0;
}

.cell {
  background: inherit;
}

.left-icon {
  line-height: 1.4;
  color: #323233;
}

.cell-panel .item {
  flex: 1;
  text-align: center;
  position: relative;
  line-height: 20px;
  padding: 10px 0;
}

.cell-panel .item:last-child:after {
  border-right: none;
}

.cell-panel .item .van-icon {
  font-size: 20px;
  color: #333333;
  width: 30px;
  height: 30px;
  line-height: 30px;
}

.cell-panel .item .title {
  white-space: nowrap;
  overflow: hidden;
  padding: 0 10px;
  text-overflow: ellipsis;
}

.cell-panel .item .text {
  color: #ff454e;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  max-width: 80px;
  margin: 0 auto;
  height: 20px;
}

.cell-panel[class*="cell-panel-"] {
  flex-flow: row wrap;
}

.cell-panel[class*="cell-panel-"] .item {
  flex: none;
}

.cell-panel.cell-panel-1 .item {
  width: 100%;
}

.cell-panel.cell-panel-2 .item {
  width: 50%;
}

.cell-panel.cell-panel-3 .item {
  width: 33.33333334%;
}

.cell-panel.cell-panel-4 .item {
  width: 25%;
}

.cell-panel.cell-panel-5 .item {
  width: 20%;
}

.foot {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: -10px;
}

.foot .icon {
  padding: 5px;
  position: relative;
  transform: rotateZ(0deg);
}

.foot .icon.upward {
  transform: rotateZ(180deg);
}

.foot .icon.down {
  transform: rotateZ(0deg);
}
</style>
