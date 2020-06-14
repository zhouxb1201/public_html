<template>
  <PopupBottom v-model="show" fullScreen showFootClose>
    <van-radio-group v-model="active" class="list">
      <van-cell-group :border="false">
        <van-cell clickable v-for="(item,index) in list" :key="index">
          <van-radio :name="item.store_id" class="item" @click="select(item)">
            <div class="info">
              <div class="name">
                <van-col span="18">{{item.store_name}}</van-col>
                <van-col span="6" class="distance">{{item.distance | distance}}</van-col>
              </div>
              <div class="detail">
                <van-col
                  span="24"
                >{{item.province_name + item.city_name + item.dictrict_name + item.address}}</van-col>
              </div>
            </div>
          </van-radio>
        </van-cell>
      </van-cell-group>
    </van-radio-group>
  </PopupBottom>
</template>

<script>
import PopupBottom from "./PopupBottom";
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    store_id: [String, Number],
    list: Array
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
    },
    active: {
      get() {
        return this.store_id && Number(this.store_id);
      },
      set(e) {}
    }
  },
  methods: {
    close() {
      this.$emit("input", false);
    },
    select(item) {
      this.$emit("select", item);
      this.close();
    }
  },
  components: {
    PopupBottom
  }
};
</script>

<style scoped>
.item {
  display: flex;
  align-items: center;
  padding: 10px 0;
}

.item >>> .van-radio__label {
  flex: 1;
}

.distance {
  text-align: right;
  white-space: nowrap;
  color: #ff454e;
  font-size: 12px;
}

.detail {
  padding: 0;
  color: #909399;
  font-size: 12px;
}
</style>