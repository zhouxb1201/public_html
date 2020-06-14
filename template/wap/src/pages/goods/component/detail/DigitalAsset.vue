<template>
  <div class="asset-group">
    <div class="asset-info" @click="showPopup">
      <van-icon
        class="icon"
        size="18px"
        :color="items[active].iconcolor"
        :name="items[active].icon"
      />
      <div class="text" :class="isFlexRow ? 'flex-row' : 'flex-column'">
        <div class="name">
          {{items[active].name}}
          <van-icon class="icon icon-down" size="14px" name="arrow-down" v-if="!isFlexRow" />
        </div>
        <span>{{items[active].money | bi}}</span>
        <span class="fee" v-if="!isFlexRow">{{fee}}手续费</span>
        <van-icon class="icon icon-down" size="14px" name="arrow-down" v-if="isFlexRow" />
      </div>
    </div>
    <div class="asset-right" v-if="isFlexRow">
      <span>{{fee}}</span>手续费
    </div>
    <van-popup v-model="show" class="asset-popup">
      <van-cell-group>
        <div class="popup-title van-hairline--top-bottom">请选择货币类型</div>
        <van-cell v-for="(item,index) in items" :key="index" clickable @click="select(index)">
          <van-icon slot="icon" :name="item.icon" :color="item.iconcolor" class="popup-icon" />
          <div slot="title" class="popup-name">{{item.name}}</div>
        </van-cell>
      </van-cell-group>
    </van-popup>
  </div>
</template>

<script>
export default {
  data() {
    return {
      active: 0,
      show: false
    };
  },
  props: {
    //是否横向布局
    isFlexRow: {
      type: Boolean,
      default: false
    },
    fee: [String],
    items: Array
  },
  methods: {
    showPopup() {
      this.show = true;
    },
    select(index) {
      this.active = index;
      this.show = false;
    }
  }
};
</script>

<style scoped>
.asset-group {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  color: #606266;
  font-size: 14px;
  line-height: 16px;
  margin-bottom: 5px;
}

.asset-popup {
  width: 80%;
  border-radius: 10px;
}
.asset-popup .popup-title {
  font-size: 16px;
  line-height: 44px;
  text-align: center;
  color: #323233;
}
.asset-popup .popup-icon {
  font-size: 20px;
  width: 24px;
  height: 24px;
  text-align: center;
  line-height: 24px;
  margin-right: 5px;
  color: #606266;
}

.asset-popup .popup-name {
  color: #606266;
}

.asset-info {
  display: flex;
  align-items: center;
}

.asset-info .text {
  padding: 0 5px;
  font-size: 12px;
  color: #606266;
}

.asset-info .text .name {
  display: flex;
}

.asset-info .text .name .icon {
  margin-left: 5px;
}

.asset-info .text.flex-row {
  display: flex;
  flex-direction: row;
}

.asset-info .text.flex-row span {
  padding: 0 5px;
}

.asset-info .text.flex-column {
  display: flex;
  flex-direction: column;
}

.asset-info .text .fee {
  font-size: 10px;
  color: #909399;
}

.asset-right {
  font-size: 12px;
  color: #909399;
}
</style>
