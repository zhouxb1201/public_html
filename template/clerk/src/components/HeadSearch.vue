<template>
  <div class="head-search">
    <div class="search-box" @click="toSearch" :style="{top}">
      <div class="left-icon e-handle" @click="onLeftClick" v-if="showLeft">
        <van-icon name="arrow-left" />
      </div>
      <van-search
        class="search"
        v-model="value"
        :disabled="disabled"
        :placeholder="placeholder||'请输入关键词'"
        :show-action="showAction"
      >
        <div slot="action" v-if="showAction" @click.stop="rightAction">{{rightActionText||'搜索'}}</div>
      </van-search>
    </div>
  </div>
</template>

<script>
import { Search } from "vant";
export default {
  data() {
    return {
      value: ""
    };
  },
  props: {
    searchType: {
      type: String,
      default: "goods"
    },
    placeholder: {
      type: String
    },
    showAction: {
      type: Boolean,
      default: false
    },
    rightActionText: {
      type: String
    },
    top: {
      type: [String]
    },
    showLeft: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: true
    },
    replace: {
      type: Boolean,
      default: false
    },
    leftClick: Function
  },
  methods: {
    toSearch() {
      if (this.disabled) {
        const obj = {
          path: "/search",
          query: {
            type: this.searchType
          }
        };
        if (!this.replace) {
          this.$router.push(obj);
        } else {
          this.$router.replace(obj);
        }
      }
    },
    onLeftClick() {
      if (this.leftClick) {
        this.leftClick();
      } else {
        this.$router.back();
      }
    },
    rightAction() {
      this.$emit("rightAction", this.value);
    }
  },
  components: {
    [Search.name]: Search
  }
};
</script>

<style scoped>
.head-search {
  background: #ffffff;
  width: 100%;
  height: 46px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #f5f5f5;
}

.head-search .left-icon {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.head-search .left-icon .van-icon {
  width: 30px;
  height: 30px;
  line-height: 30px;
  text-align: center;
  font-size: 16px;
  font-weight: 800;
  color: #666;
}

.head-search .search-box {
  position: fixed;
  top: 0;
  left: 0;
  height: 46px;
  width: 100%;
  z-index: 1002;
  overflow: hidden;
  background: #fff;
  border-bottom: 2px solid #f5f5f5;
  display: flex;
  align-items: center;
}

.search {
  width: 100%;
  padding: 5px;
  margin-left: 5px;
  background: none !important;
}
</style>
