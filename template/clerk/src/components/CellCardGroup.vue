<template>
  <van-cell-group class="cell-group card-group-box">
    <van-cell :title="title" :icon="icon" :value="value" v-if="!$slots.head && showHead"/>
    <slot name="head"/>
    <div class="cell-card" :class="colsClass">
      <div class="item e-handle" v-for="(item,index) in items" :key="index" @click="onClick(item)">
        <div class="badge" v-if="item.badge">{{item.badge}}</div>
        <van-icon :name="'v-icon-'+item.icon"/>
        <div class="title">{{item.text}}</div>
      </div>
    </div>
  </van-cell-group>
</template>

<script>
export default {
  data() {
    return {};
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
    icon: {
      type: String
    },
    value: {
      type: String
    },
    cols: {
      type: [String, Number],
      default: 0
    }
  },
  computed: {
    colsClass() {
      return this.cols ? "cell-card-" + this.cols : null;
    }
  },
  methods: {
    onClick(item) {
      this.$emit("click", item);
    }
  }
};
</script>

<style scoped>
.cell-group {
  margin-bottom: 10px;
}

.cell-card {
  display: flex;
  flex-flow: wrap;
  padding: 10px 0;
}

.cell-card .item {
  flex: 1;
  text-align: center;
  position: relative;
  line-height: 20px;
}

.cell-card .item::after {
  border-right-width: 1px;
}

.cell-card .item:last-child:after {
  border-right: none;
}

.cell-card .item .van-icon {
  font-size: 20px;
  color: #333333;
  width: 30px;
  height: 30px;
  line-height: 30px;
}

.cell-card .item .badge {
  position: absolute;
  right: 10px;
  line-height: 1;
  padding: 0.3em;
  font-size: 12px;
  transform: scale(0.9);
  border-radius: 0.8em;
  background: red;
  color: #fff;
  z-index: 9;
  min-width: 1.5em;
  max-width: 28px;
  white-space: nowrap;
  overflow: hidden;
  display: block;
  text-align: center;
}

.cell-card .item .title {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  height: 20px;
}

.cell-card[class*="cell-card-"] {
  flex-flow: row wrap;
}

.cell-card[class*="cell-card-"] .item {
  padding: 10px 0;
  flex: none;
}

.cell-card.cell-card-1 .item {
  width: 100%;
}

.cell-card.cell-card-2 .item {
  width: 50%;
}

.cell-card.cell-card-3 .item {
  width: 33.33333334%;
}

.cell-card.cell-card-4 .item {
  width: 25%;
}

.cell-card.cell-card-5 .item {
  width: 20%;
}
</style>
