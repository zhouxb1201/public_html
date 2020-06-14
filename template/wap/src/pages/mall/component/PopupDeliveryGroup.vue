<template>
  <PopupBottom v-model="show">
    <div
      slot="title"
      class="van-hairline--top-bottom van-actionsheet__header title"
    >
      <span class="title-text">配送</span>
      <span class="title-tip">(不同门店的库存或价格会有偏差)</span>
      <van-icon name="close" class="icon-close" @click="show = false" />
    </div>
    <van-radio-group v-model="active" class="list" @change="change">
      <van-cell-group
        :border="false"
        v-if="isShowExpress.has_express == ''"
        title="快递配送"
        class="cell-group-address"
      >
        <van-cell clickable>
          <van-radio :name="-1" class="item">
            <div class="info">
              <div class="detail">
                <van-col span="24">线上物流配送，由店铺为您发货。</van-col>
              </div>
            </div>
          </van-radio>
        </van-cell>
      </van-cell-group>
      <van-cell-group
        :border="false"
        title="门店自提"
        class="cell-group-store"
        v-if="list.length && isShowExpress.has_store == 1"
      >
        <van-cell clickable v-for="(item, s) in list" :key="s">
          <van-radio :name="s" class="item">
            <div class="info">
              <div class="name">
                <van-col span="18">{{ item.store_name }}</van-col>
                <van-col span="6" class="distance">
                  {{ item.distance | distance }}
                </van-col>
              </div>
              <div class="detail">
                <van-col span="24">{{ item.address }}</van-col>
              </div>
            </div>
          </van-radio>
        </van-cell>
      </van-cell-group>
    </van-radio-group>
  </PopupBottom>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
export default {
  data() {
    return {
      active: -1
    };
  },
  props: {
    value: Boolean,
    list: Array,
    isShowExpress: Object
  },
  filters: {
    distance(value) {
      return value + "km";
    }
  },
  computed: {
    show: {
      get() {
        return this.value;
      },
      set(e) {
        this.$emit("input", e);
      }
    }
  },
  methods: {
    change(index) {
      this.$emit("select", index);
      this.$emit("input", false);
    }
  },
  components: {
    PopupBottom
  }
};
</script>

<style scoped>
.title {
  display: flex;
  flex-flow: column;
  line-height: 1;
  padding: 5px 0;
}

.title-text {
  line-height: 24px;
}

.title-tip {
  line-height: 20px;
  font-size: 12px;
  color: #909399;
}

.cell-group-address {
  margin-bottom: 10px;
  border-bottom: 10px solid #f8f8f8;
}

.title .icon-close {
  line-height: 54px;
}

.list .item {
  display: flex;
  align-items: center;
}

.list .item >>> .van-radio__label {
  flex: 1;
}

.list .distance {
  text-align: right;
  white-space: nowrap;
  color: #ff454e;
  font-size: 12px;
}

.list .detail {
  padding: 0;
  color: #909399;
  font-size: 12px;
}
</style>
