<template>
  <van-row
    type="flex"
    justify="space-between"
    class="item"
    :class="itemBg"
    @click.native="$router.push('/consumercard/detail/'+item.card_id)"
  >
    <van-col span="18" class="info">
      <div class="name">{{item.goods_name}}</div>
      <div class="time">
        有效期至：
        <span>{{item.invalid_time}}</span>
      </div>
    </van-col>
    <van-col span="6" class="num">
      <div>{{numText}}</div>
      <div class="strong" v-if="numText != '最后一天'">{{item.surplus_num}}</div>
    </van-col>
  </van-row>
</template>

<script>
export default {
  props: {
    item: Object
  },
  computed: {
    itemBg() {
      let className = "";
      if (this.item.state == 2) {
        className = "disabled-bg";
      } else {
        className = this.item.type == 1 ? "time-bg" : "number-bg";
      }
      return className;
    },
    numText() {
      const { type, state, surplus_num } = this.item;
      let text = "";
      if (type == 1) {
        text = state != 2 && surplus_num == 0 ? "最后一天" : "剩余天数";
      } else {
        text = "剩余次数";
      }
      return text;
    }
  }
};
</script>

<style scoped>
.item {
  border-radius: 4px;
  background: #f1f1f1;
  padding: 10px 15px;
  margin: 15px;
  color: #fff;
}

.item .info {
  line-height: 1.8;
}

.item .num {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
}

.item .num .strong {
  font-size: 1.4em;
  padding-top: 6px;
}

.item .time {
  font-size: 12px;
}

.number-bg {
  background: #2c9cf0;
}

.time-bg {
  background: #ffab32;
}

.disabled-bg {
  background: #999;
}
</style>
