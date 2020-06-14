<template>
  <van-popup
    v-model="show"
    position="right"
    :close-on-click-overlay="false"
    @click-overlay="closePopup"
  >
    <div class="screen-popup">
      <div class="screen-condition">
        <div class="btn-group">
          <div class="btn-box" v-for="(item,index) in tag" :key="index">
            <van-button
              size="small"
              class="btn"
              block
              :type="item.selected ? 'danger' : 'default'"
              @click="tagSelect(index,item.selected)"
            >{{item.name}}</van-button>
          </div>
        </div>
        <div class="price-range">价格区间</div>
        <div class="condition-group">
          <div class="input-group">
            <van-field type="number" v-model="params.min_price" placeholder="最低价"/>
            <span class="input-group-addon">~</span>
            <van-field type="number" v-model="params.max_price" placeholder="最高价"/>
          </div>
        </div>
      </div>
      <div class="foot">
        <div class="btn reset e-handle" @click="onReset">重置</div>
        <div class="btn sub e-handle" @click="onOonfirm">确定</div>
      </div>
    </div>
  </van-popup>
</template>
<script>
export default {
  data() {
    return {
      tag: [
        {
          name: "推荐",
          type: "is_recommend",
          selected: false
        },
        {
          name: "新品",
          type: "is_new",
          selected: false
        },
        {
          name: "热卖",
          type: "is_hot",
          selected: false
        },
        {
          name: "促销",
          type: "is_promotion",
          selected: false
        },
        {
          name: "包邮",
          type: "is_shipping_free",
          selected: false
        }
      ],
      params: {
        min_price: "",
        max_price: "",
        is_recommend: 0,
        is_new: 0,
        is_hot: 0,
        is_promotion: 0,
        is_shipping_free: 0
      }
    };
  },
  props: {
    show: Boolean,
    default: false
  },
  methods: {
    // 选择标签
    tagSelect(index, flag) {
      const $this = this;
      $this.tag[index].selected = !flag;
      $this.params[$this.tag[index].type] = $this.tag[index].selected ? 1 : 0;
    },
    closePopup() {
      this.$emit("popup", false);
    },
    // 重置筛选
    onReset() {
      const $this = this;
      $this.params.min_price = "";
      $this.params.max_price = "";
      $this.params.is_recommend = 0;
      $this.params.is_new = 0;
      $this.params.is_hot = 0;
      $this.params.is_promotion = 0;
      $this.params.is_shipping_free = 0;
      $this.tag.forEach(e => {
        e.selected = false;
      });
    },
    // 确认筛选
    onOonfirm() {
      const $this = this;
      $this.params.min_price = $this.params.min_price
        ? parseFloat($this.params.min_price)
        : "";
      $this.params.max_price = $this.params.max_price
        ? parseFloat($this.params.max_price)
        : "";
      $this.$emit("screen", $this.params);
    }
  }
};
</script>
<style scoped>
.van-popup {
  width: 60%;
  height: 100%;
}

.screen-popup {
  position: relative;
  width: 100%;
  height: 100%;
  background: #ffffff;
  overflow: hidden;
}

.condition-group {
  display: flex;
  padding: 10px;
  border-bottom: 1px solid #ddd;
}
.btn-group {
  display: flex;
  flex-wrap: wrap;
  padding: 5px;
  border-bottom: 1px solid #ddd;
}
.btn-group .btn-box {
  width: 50%;
  padding: 5px;
}

.btn-group .btn.selected {
  background: #ff454e;
  color: #ffffff;
}

.price-range {
  padding: 20px 10px 0px;
  color: #666;
}

.input-group {
  display: flex;
  align-items: center;
}

.input-group .van-field {
  padding: 6px 12px;
  border: 1px solid #dddddd;
}

.input-group .input-group-addon {
  padding: 10px;
  color: #666;
}

.screen-popup .foot {
  position: absolute;
  bottom: 0;
  display: flex;
  z-index: 1;
  width: 100%;
  align-items: center;
}

.screen-popup .foot .btn {
  flex: 1;
  text-align: center;
  line-height: 50px;
  height: 50px;
}

.screen-popup .foot .btn.reset {
  background: #f8f8f8;
  color: #666;
}

.screen-popup .foot .btn.sub {
  background: #ff454e;
  color: #ffffff;
}
</style>
